<?php

use App\Mattermost\Client;
use App\Tools\ChannelCreate;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    config([
        'kit.mattermost.url' => 'http://mm.test',
        'kit.mattermost.token' => 'tok',
        'kit.mattermost.team_id' => 'team-1',
    ]);
});

test('returns existing channel without creating', function () {
    Http::fake([
        'http://mm.test/api/v4/teams/team-1/channels/name/mesa-studio' => Http::response([
            'id' => 'ch-exists',
            'name' => 'mesa-studio',
        ], 200),
    ]);

    $out = (string) (new ChannelCreate)->handle(new Request(['name' => 'mesa-studio']));

    expect($out)->toBe('exists #mesa-studio id=ch-exists');
    Http::assertNotSent(fn ($req) => $req->url() === 'http://mm.test/api/v4/channels' && $req->method() === 'POST');
});

test('creates an open channel and adds members', function () {
    Http::fake([
        'http://mm.test/api/v4/teams/team-1/channels/name/shop-floor' => Http::response(['id' => 'missing'], 404),
        'http://mm.test/api/v4/channels' => Http::response(['id' => 'ch-new', 'name' => 'shop-floor'], 201),
        'http://mm.test/api/v4/users/username/jordan' => Http::response(['id' => 'u-j'], 200),
        'http://mm.test/api/v4/channels/ch-new/members' => Http::response(['user_id' => 'u-j'], 201),
    ]);

    $out = (string) (new ChannelCreate)->handle(new Request([
        'name' => 'Shop Floor',
        'purpose' => 'factory',
        'add' => '@jordan',
    ]));

    expect($out)->toBe('created #shop-floor id=ch-new added=@jordan');
});

test('client slugs names', function () {
    Http::fake([
        'http://mm.test/api/v4/teams/team-1/channels/name/mesa-studio' => Http::response([
            'id' => 'ch-1',
            'name' => 'mesa-studio',
        ], 200),
    ]);

    $out = (new Client)->createChannel('#Mesa Studio');
    expect($out)->toMatchArray(['id' => 'ch-1', 'created' => false, 'name' => 'mesa-studio']);
});
