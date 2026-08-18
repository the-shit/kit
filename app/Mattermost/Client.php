<?php

namespace App\Mattermost;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    public function post(string $channel, string $text): void
    {
        if ($this->base() === null || $channel === '') {
            Log::warning('kit mm post skipped: missing url/token/channel');

            return;
        }

        $this->http()->post($this->base().'/api/v4/posts', [
            'channel_id' => $channel,
            'message' => $text,
        ]);
    }

    /**
     * Find or create an open team channel. Returns id + created flag.
     *
     * @return array{id: string, name: string, created: bool}|array{error: string}
     */
    public function createChannel(string $name, string $display = '', string $purpose = ''): array
    {
        $slug = $this->slug($name);
        if ($slug === '') {
            return ['error' => 'channel name required'];
        }
        $base = $this->base();
        $team = (string) config('kit.mattermost.team_id');
        if ($base === null || $team === '') {
            return ['error' => 'mattermost url/token/team missing'];
        }

        $existing = $this->http()->get($base.'/api/v4/teams/'.$team.'/channels/name/'.$slug);
        if ($existing->successful() && is_string($existing->json('id'))) {
            return ['id' => $existing->json('id'), 'name' => $slug, 'created' => false];
        }

        $res = $this->http()->post($base.'/api/v4/channels', [
            'team_id' => $team,
            'name' => $slug,
            'display_name' => $display !== '' ? $display : $slug,
            'purpose' => $purpose,
            'type' => 'O',
        ]);
        if (! $res->successful() || ! is_string($res->json('id'))) {
            return ['error' => 'create failed: '.($res->json('message') ?? $res->status())];
        }

        return ['id' => $res->json('id'), 'name' => $slug, 'created' => true];
    }

    public function addMember(string $channel, string $userId): bool
    {
        $base = $this->base();
        if ($base === null || $channel === '' || $userId === '') {
            return false;
        }
        $res = $this->http()->post($base.'/api/v4/channels/'.$channel.'/members', [
            'user_id' => $userId,
        ]);

        return $res->successful();
    }

    public function resolveUser(string $who): ?string
    {
        $who = ltrim(trim($who), '@');
        if ($who === '') {
            return null;
        }
        $base = $this->base();
        if ($base === null) {
            return null;
        }
        if (strlen($who) === 26 && ctype_alnum($who)) {
            return $who;
        }
        $res = $this->http()->get($base.'/api/v4/users/username/'.$who);
        $id = $res->json('id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function slug(string $name): string
    {
        $name = strtolower(trim($name));
        $name = ltrim($name, '#');

        return preg_replace('/[^a-z0-9-]/', '-', $name) ?? '';
    }

    private function http(): PendingRequest
    {
        return Http::timeout(20)
            ->withToken((string) config('kit.mattermost.token'))
            ->acceptJson();
    }

    private function base(): ?string
    {
        $url = rtrim((string) config('kit.mattermost.url'), '/');
        $token = (string) config('kit.mattermost.token');
        if ($url === '' || $token === '') {
            return null;
        }

        return $url;
    }
}
