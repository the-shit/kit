<?php

namespace App\Tools;

use App\Mattermost\Client;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class ChannelCreate implements Tool
{
    public function description(): Stringable|string
    {
        return 'Find or create a Mattermost open channel on the factory team. '
            .'Pass name (slug). Optional display, purpose, and add (comma @users or ids). '
            .'Returns channel id. Use for hallway channels. Does not post as a second mouth.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Channel slug, e.g. mesa-studio.')->required(),
            'display' => $schema->string()->description('Optional display name.'),
            'purpose' => $schema->string()->description('Optional purpose line.'),
            'add' => $schema->string()->description('Optional comma @usernames or user ids to add.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $name = trim((string) ($request['name'] ?? ''));
        $mm = app(Client::class);
        $out = $mm->createChannel(
            $name,
            trim((string) ($request['display'] ?? '')),
            trim((string) ($request['purpose'] ?? '')),
        );
        if (isset($out['error'])) {
            return 'channel: '.$out['error'];
        }

        $added = [];
        $add = trim((string) ($request['add'] ?? ''));
        if ($add !== '') {
            foreach (preg_split('/\s*,\s*/', $add) ?: [] as $who) {
                $uid = $mm->resolveUser($who);
                if ($uid !== null && $mm->addMember($out['id'], $uid)) {
                    $added[] = $who;
                }
            }
        }

        $flag = ($out['created'] ?? false) ? 'created' : 'exists';

        return $flag.' #'.$out['name'].' id='.$out['id']
            .($added !== [] ? ' added='.implode(',', $added) : '');
    }
}
