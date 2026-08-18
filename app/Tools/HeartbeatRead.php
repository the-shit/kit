<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class HeartbeatRead implements Tool
{
    public function description(): Stringable|string
    {
        return 'Read Loki factory heartbeat: kitd, vite, blender, and the last stamp. '
            .'Use when Jordan asks if you are up or if tools are down.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $path = (string) config('kit.status_path');
        if ($path !== '' && is_file($path)) {
            return (string) File::get($path);
        }

        try {
            $health = Http::timeout(2)->get((string) config('kit.kitd_health'));
            if ($health->successful()) {
                return $health->body();
            }
        } catch (\Throwable $e) {
            return 'heartbeat missing: '.$e->getMessage();
        }

        return 'heartbeat missing';
    }
}
