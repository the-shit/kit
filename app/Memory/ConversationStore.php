<?php

namespace App\Memory;

use Illuminate\Support\Facades\File;

class ConversationStore
{
    /** @return list<array{role: string, content: string}> */
    public function history(string $thread): array
    {
        $path = $this->path($thread);
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? array_values($data) : [];
    }

    public function push(string $thread, string $role, string $content): void
    {
        $rows = $this->history($thread);
        $rows[] = ['role' => $role, 'content' => $content];
        if (count($rows) > 40) {
            $rows = array_slice($rows, -40);
        }
        File::ensureDirectoryExists(dirname($this->path($thread)));
        File::put($this->path($thread), json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function path(string $thread): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $thread) ?: 'default';

        $root = (string) config('kit.conversations_path', storage_path('app/kit/conversations'));

        return $root.'/'.$safe.'.json';
    }
}
