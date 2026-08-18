<?php

namespace App\Http\Controllers;

use App\Jobs\ReplyOnMattermost;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MattermostWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $expected = (string) config('kit.webhook_token');
        $got = (string) $request->input('token', $request->header('X-Webhook-Token', ''));
        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response('unauthorized', 401);
        }

        $userId = (string) $request->input('user_id', '');
        $kitId = (string) config('kit.mattermost.user_id');
        $jordan = (string) config('kit.mattermost.jordan_user_id');
        if ($userId === $kitId || ($jordan !== '' && $userId !== $jordan)) {
            return response('', 200);
        }

        $text = trim((string) $request->input('text', $request->input('message', '')));
        if ($text === '') {
            return response('', 200);
        }

        $channel = (string) $request->input('channel_id', config('kit.mattermost.channel_id'));
        ReplyOnMattermost::dispatch($channel !== '' ? $channel : 'mm', $text);

        return response('', 200);
    }
}
