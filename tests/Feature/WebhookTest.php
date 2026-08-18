<?php

use App\Jobs\ReplyOnMattermost;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    config([
        'kit.webhook_token' => 'secret-hook',
        'kit.peer_token' => 'secret-peer',
        'kit.mattermost.user_id' => 'kit-user',
        'kit.mattermost.jordan_user_id' => 'jordan-user',
        'kit.mattermost.token' => '',
    ]);
});

test('webhook rejects bad token', function () {
    $this->post('/api/webhooks/mattermost', [
        'token' => 'nope',
        'user_id' => 'jordan-user',
        'text' => 'hi',
    ])->assertUnauthorized();
});

test('webhook ignores kit and empty text', function () {
    $this->post('/api/webhooks/mattermost', [
        'token' => 'secret-hook',
        'user_id' => 'kit-user',
        'text' => 'loop',
    ])->assertOk();

    $this->post('/api/webhooks/mattermost', [
        'token' => 'secret-hook',
        'user_id' => 'jordan-user',
        'text' => '',
    ])->assertOk();
});

test('ask requires bearer', function () {
    $this->postJson('/api/ask', ['message' => 'hi'])->assertUnauthorized();
});

test('jordan text is queued, not run inline', function () {
    $this->post('/api/webhooks/mattermost', [
        'token' => 'secret-hook',
        'user_id' => 'jordan-user',
        'channel_id' => 'dm-1',
        'text' => 'catalog please',
    ])->assertOk();

    Queue::assertPushed(ReplyOnMattermost::class, function (ReplyOnMattermost $job) {
        return $job->channel === 'dm-1' && $job->message === 'catalog please';
    });
});
