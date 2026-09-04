<?php

namespace App\Agent;

use App\Factory\Snapshot;
use App\Memory\FileMemory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

class PromptBuilder
{
    public function __construct(
        private readonly Snapshot $snapshot,
        private readonly FileMemory $memory,
    ) {}

    public function build(string $scratchpad = ''): string
    {
        $now = CarbonImmutable::now('America/Phoenix');
        $parts = [
            "Today is {$now->toDateString()} ({$now->format('l, F j g:ia T')}).",
            "## Identity\n".$this->identityBlock(),
            "## Hard rules\n".$this->file('prompts/hard-rules.md'),
            "## Model\n".$this->file('prompts/model.md'),
            "## Factory (live)\n".$this->snapshot->render(),
            "## Memory (pinned)\n".$this->memory->render(8),
        ];

        if (trim($scratchpad) !== '') {
            $parts[] = "## Scratchpad\n".$scratchpad;
        }

        $parts[] = "## Mission\n".$this->file('prompts/mission.md');

        return implode("\n\n", $parts);
    }

    private function identityBlock(): string
    {
        $path = base_path('identities/kit.json');
        $id = File::isFile($path) ? json_decode((string) File::get($path), true) : [];
        if (! is_array($id)) {
            $id = [];
        }

        $name = $id['name'] ?? 'Kit';
        $born = $id['born_at'] ?? '2026-08-17';
        $purpose = rtrim((string) ($id['purpose'] ?? 'factory'), '.');
        $owner = $id['owner_id'] ?? 'jordan';
        $relations = implode(', ', $id['relations'] ?? ['lexi']);

        return "You are {$name}, born {$born}. {$purpose}.\nYou act on behalf of {$owner}.\nSiblings: {$relations}. Loki only — not a Lexi citizen.";
    }

    private function file(string $relative): string
    {
        $path = base_path($relative);

        return File::isFile($path) ? trim((string) File::get($path)) : '';
    }
}
