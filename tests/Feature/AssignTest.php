<?php

use App\Factory\Board;
use App\Jobs\DispatchKitWork;
use App\Mattermost\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $path = storage_path('framework/testing/board-assign-'.uniqid('', true).'.json');
    config([
        'kit.board_path' => $path,
        'kit.peer_token' => 'test-peer',
        'kit.mattermost.url' => 'http://mm.test',
        'kit.mattermost.token' => 'mm-tok',
        'kit.mattermost.hallway_id' => 'hall-1',
        'kit.mattermost.dm_id' => 'dm-1',
    ]);
    $this->boardPath = $path;
});

afterEach(function () {
    @unlink($this->boardPath);
});

it('rejects assign without bearer', function () {
    $this->postJson('/api/assign', ['brief' => 'make a flyer'])->assertUnauthorized();
});

it('rejects empty assign', function () {
    $this->withToken('test-peer')
        ->postJson('/api/assign', [])
        ->assertStatus(422);
});

it('queues work and writes a board row without starting the Kit LLM', function () {
    Queue::fake();

    $this->withToken('test-peer')
        ->postJson('/api/assign', [
            'issue' => 'https://github.com/the-shit/bikes-v2/issues/12',
            'chair' => 'kit',
            'brief' => 'rider stills',
        ])
        ->assertStatus(202)
        ->assertJsonPath('ok', true)
        ->assertJsonPath('board_id', 'rider');

    Queue::assertPushed(DispatchKitWork::class, function (DispatchKitWork $job): bool {
        return $job->boardId === 'rider' && $job->chair === 'kit';
    });

    $row = collect(app(Board::class)->read()['items'])->firstWhere('id', 'rider');
    expect($row['lifecycle'])->toBe('queued')
        ->and($row['owner'])->toBe('kit')
        ->and($row['issue'])->toBe(12);
});

it('accepts make_image without a github issue', function () {
    Queue::fake();

    $this->withToken('test-peer')
        ->postJson('/api/assign', [
            'kind' => 'make_image',
            'chair' => 'kit',
            'brief' => 'Lexi yoga still',
        ])
        ->assertStatus(202)
        ->assertJsonPath('board_id', 'make_image');
});

it('posts the hallway receipt when the job runs', function () {
    Http::fake([
        'http://mm.test/api/v4/posts' => Http::response(['id' => 'p1'], 201),
    ]);

    (new DispatchKitWork('issue-12', 'kit', 'rider stills', 'https://github.com/the-shit/bikes-v2/issues/12', ''))
        ->handle(app(Client::class));

    Http::assertSent(function ($req): bool {
        return $req->url() === 'http://mm.test/api/v4/posts'
            && str_contains((string) $req['message'], 'issue-12')
            && str_contains((string) $req['message'], '_asgard assign_');
    });
});

it('skips mattermost when hallway is the Jordan DM', function () {
    config(['kit.mattermost.hallway_id' => 'dm-1']);
    Http::fake();

    (new DispatchKitWork('issue-5', 'kit', 'x', 'https://github.com/the-shit/kit/issues/5', ''))
        ->handle(app(Client::class));

    Http::assertNothingSent();
});
