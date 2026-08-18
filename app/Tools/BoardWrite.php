<?php

namespace App\Tools;

use App\Factory\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

readonly class BoardWrite implements Tool
{
    public function description(): Stringable|string
    {
        return 'Upsert one factory board row (flock on board.json). '
            .'id is a catalog id or token (ranch-7620, blender, next). '
            .'state is a short free string. lifecycle is optional queued|wip|cut|pr|live|blocked. '
            .'Snapshot wins over chat. Do not invent a catalog URL.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Board row id.')->required(),
            'state' => $schema->string()->description('Short free string (cut, m1, hero-ebike, cli).')->required(),
            'lifecycle' => $schema->string()->description('Optional queued|wip|cut|pr|live|blocked.'),
            'owner' => $schema->string()->description('Optional kit|feel|bench|lexi|jordan.'),
            'issue' => $schema->integer()->description('Optional bikes-v2 issue number.'),
            'pr' => $schema->string()->description('Optional PR URL.'),
            'note' => $schema->string()->description('Optional one-line note.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $id = trim((string) ($request['id'] ?? ''));
        $state = trim((string) ($request['state'] ?? ''));
        if ($id === '' || $state === '') {
            return 'id and state are required';
        }

        $fields = ['state' => $state];
        foreach (['lifecycle', 'owner', 'pr', 'note'] as $key) {
            $val = $request[$key] ?? null;
            if ($val !== null && $val !== '') {
                $fields[$key] = $val;
            }
        }
        $issue = $request['issue'] ?? null;
        if ($issue !== null && $issue !== '') {
            $fields['issue'] = $issue;
        }

        try {
            $item = app(Board::class)->upsert($id, $fields);
        } catch (RuntimeException $e) {
            return 'board: '.$e->getMessage();
        }

        return json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
