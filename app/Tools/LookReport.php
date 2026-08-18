<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class LookReport implements Tool
{
    public function description(): Stringable|string
    {
        return 'Read the last Playwright rider look-compare report '
            .'(tmp/rider-look/report.json). Returns region checks '
            .'(hair, glasses, beard, tee, shorts, flops). Does not run Playwright.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'unused' => $schema->boolean()->nullable()->description('Unused. xAI rejects an empty properties object.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $path = (string) config('kit.look_report');
        if ($path === '' || ! is_file($path)) {
            return 'look report missing. On Loki: BIKES_URL=http://127.0.0.1:5173 npx playwright test e2e/rider-look.spec.ts';
        }

        $raw = File::get($path);

        return $raw !== '' ? $raw : 'look report unreadable';
    }
}
