<?php

namespace App\Tools;

use App\Memory\FileMemory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class MemoryStore implements Tool
{
    public function description(): Stringable|string
    {
        return 'Pin a factory fact to Kit memory (file-backed knowledge_kilt). '
            .'Use for locks Jordan states: M1 rider, next mesh, stack decisions.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->description('The fact to remember.')->required(),
            'tags' => $schema->string()->description('Optional comma-separated tags.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $text = trim((string) ($request['text'] ?? ''));
        if ($text === '') {
            return 'text is required';
        }

        $tags = array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) ($request['tags'] ?? '')),
        )));

        $id = app(FileMemory::class)->store($text, $tags);

        return "stored {$id}";
    }
}
