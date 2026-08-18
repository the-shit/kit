<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class AskLexi implements Tool
{
    public function description(): Stringable|string
    {
        return 'Ask Lexi (Chief of Staff) via her MCP. Use for taste, Jordan-life context, '
            .'or board locks. Not for catalog numbers or Blender — those are yours.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('What to ask Lexi.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $message = trim((string) ($request['message'] ?? ''));
        if ($message === '') {
            return 'message is required';
        }

        $bin = (string) config('kit.ask_lexi');
        if ($bin === '' || ! is_file($bin)) {
            return 'ask-lexi script missing';
        }

        $result = Process::timeout(180)->run([$bin, $message]);
        $out = trim($result->output().$result->errorOutput());

        return $out !== '' ? $out : 'Lexi returned empty (exit '.$result->exitCode().')';
    }
}
