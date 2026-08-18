<?php

namespace App\Memory;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileMemory
{
    public function path(): string
    {
        return (string) config('kit.memory_path', storage_path('app/kit/memory.json'));
    }

    /** @return list<array{id: string, text: string, tags: list<string>, at: string}> */
    public function all(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? array_values($data) : [];
    }

    /** @param list<string> $tags */
    public function store(string $text, array $tags = []): string
    {
        $rows = $this->all();
        $id = Str::lower(Str::random(10));
        $rows[] = [
            'id' => $id,
            'text' => trim($text),
            'tags' => array_values($tags),
            'at' => now('America/Phoenix')->toIso8601String(),
        ];
        $this->write($rows);

        return $id;
    }

    /** @return list<array{id: string, text: string, tags: list<string>, at: string}> */
    public function search(string $query, int $limit = 8): array
    {
        $q = Str::lower($query);
        $hits = [];
        foreach ($this->all() as $row) {
            $hay = Str::lower($row['text'].' '.implode(' ', $row['tags'] ?? []));
            if ($q === '' || str_contains($hay, $q)) {
                $hits[] = $row;
            }
        }

        return array_slice(array_reverse($hits), 0, $limit);
    }

    public function render(int $limit = 8): string
    {
        $rows = array_slice(array_reverse($this->all()), 0, $limit);
        if ($rows === []) {
            return '(empty)';
        }

        return implode("\n", array_map(
            fn (array $row): string => '- '.$row['text'],
            $rows,
        ));
    }

    /** @param list<array{id: string, text: string, tags: list<string>, at: string}> $rows */
    private function write(array $rows): void
    {
        $dir = dirname($this->path());
        File::ensureDirectoryExists($dir);
        File::put($this->path(), json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }
}
