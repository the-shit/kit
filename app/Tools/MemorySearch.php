<?php

namespace App\Tools;

use App\Memory\FileMemory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class MemorySearch implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search Kit file memory. Pass a keyword (rider, ebike, stack, lock).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Keyword to match.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) ($request['query'] ?? ''));
        $hits = app(FileMemory::class)->search($query);
        if ($hits === []) {
            return 'no hits';
        }

        return implode("\n", array_map(
            fn (array $row): string => $row['at'].' '.$row['text'],
            $hits,
        ));
    }
}
