<?php

namespace App\Mattermost;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    public function post(string $channel, string $text): void
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
