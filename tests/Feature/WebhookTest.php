<?php

beforeEach(function () {
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
