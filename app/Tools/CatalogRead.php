<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class CatalogRead implements Tool
{
    public function description(): Stringable|string
    {
        return 'Read bikes-v2 public/models/catalog.json. '
            .'Pass id for one row (url, sockets, refs). Omit id to list ids. '
            .'Gameplay loads catalog ids only — never invent a new GLB URL.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Optional catalog id (rider, hero-ebike, ranch-7620).'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $id = trim((string) ($request['id'] ?? ''));
        $path = (string) config('kit.catalog_path');
        if ($path === '' || ! is_file($path)) {
            return 'catalog missing';
        }

        $catalog = json_decode((string) File::get($path), true);
        $assets = is_array($catalog) ? ($catalog['assets'] ?? []) : [];
        if (! is_array($assets)) {
            return 'catalog.assets missing';
        }

        if ($id === '') {
            $rows = [];
            foreach ($assets as $row) {
                if (is_array($row)) {
                    $rows[] = ($row['id'] ?? '?').' → '.($row['url'] ?? '');
                }
            }

            return implode("\n", $rows);
        }

        foreach ($assets as $row) {
            if (is_array($row) && ($row['id'] ?? '') === $id) {
                return json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
            }
        }

        return "catalog: missing {$id}";
    }
}
