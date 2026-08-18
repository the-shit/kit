<?php

namespace App\Http\Controllers;

use App\Agent\Runner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MattermostWebhookController extends Controller
{
    public function __invoke(Request $request, Runner $runner): Response
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
        $reply = $runner->say($channel !== '' ? $channel : 'mm', $text);
        $this->post($channel, $reply);

        return response('', 200);
    }

    private function post(string $channel, string $text): void
    {
        $url = rtrim((string) config('kit.mattermost.url'), '/');
        $token = (string) config('kit.mattermost.token');
        if ($url === '' || $token === '' || $channel === '') {
            Log::warning('kit mm post skipped: missing url/token/channel');

            return;
        }

        Http::timeout(20)
            ->withToken($token)
            ->acceptJson()
            ->post($url.'/api/v4/posts', [
                'channel_id' => $channel,
                'message' => $text,
            ]);
    }
}
