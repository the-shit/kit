<?php

use App\Factory\Board;
use App\Mattermost\Client;

beforeEach(function () {
    $path = storage_path('framework/testing/board-'.uniqid('', true).'.json');
    config([
        'kit.board_path' => $path,
        'kit.peer_token' => 'test-peer',
        'kit.mattermost.hallway_id' => 'hall-1',
        'kit.mattermost.dm_id' => 'dm-1',
    ]);
    $this->boardPath = $path;
});

afterEach(function () {
    @unlink($this->boardPath);
});

test('assign refuses a bad bearer', function () {
    $this->postJson('/api/assign', ['issue' => 'https://github.com/the-shit/bikes-v2/issues/12'])
        ->assertUnauthorized();
});

test('assign requires an issue', function () {
    $this->withToken('test-peer')
        ->postJson('/api/assign', ['brief' => 'no issue'])
        ->assertStatus(422);
});

test('assign queues a board row and does not speak as the mouth', function () {
    $mm = Mockery::mock(Client::class);
    $mm->shouldReceive('post')->once()->with('hall-1', 'queued rider ← #12 (kit)');
    $this->app->instance(Client::class, $mm);

    $this->withToken('test-peer')
        ->postJson('/api/assign', [
            'issue' => 'https://github.com/the-shit/bikes-v2/issues/12',
            'chair' => 'kit',
            'brief' => 'rider stills',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('board_id', 'rider');

    $row = collect(app(Board::class)->read()['items'])->firstWhere('id', 'rider');
    expect($row['lifecycle'])->toBe('queued')
        ->and($row['owner'])->toBe('kit')
        ->and($row['issue'])->toBe(12);
});

test('assign skips mattermost when hallway is the Jordan DM', function () {
    config(['kit.mattermost.hallway_id' => 'dm-1']);
    $mm = Mockery::mock(Client::class);
    $mm->shouldReceive('post')->never();
    $this->app->instance(Client::class, $mm);

    $this->withToken('test-peer')
        ->postJson('/api/assign', [
            'issue' => 'https://github.com/the-shit/kit/issues/5',
            'chair' => 'kit',
        ])
        ->assertOk()
        ->assertJsonPath('board_id', 'issue-5');
});
