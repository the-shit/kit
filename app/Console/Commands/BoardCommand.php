<?php

namespace App\Console\Commands;

use App\Factory\Board;
use Illuminate\Console\Command;
use RuntimeException;

class BoardCommand extends Command
{
    protected $signature = 'kit:board
        {id : Row id (ranch-7620, blender, next)}
        {state : Short free string}
        {--lifecycle= : queued|wip|cut|pr|live|blocked}
        {--owner= : kit|feel|bench|lexi|jordan}
        {--issue= : bikes-v2 issue number}
        {--pr= : PR URL}
        {--note= : One line}';

    protected $description = 'Upsert a factory board row (flock). Grok sessions use bin/board-write.';

    public function handle(Board $board): int
    {
        $fields = ['state' => (string) $this->argument('state')];
        foreach (['lifecycle', 'owner', 'pr', 'note'] as $key) {
            $val = $this->option($key);
            if ($val !== null && $val !== '') {
                $fields[$key] = $val;
            }
        }
        $issue = $this->option('issue');
        if ($issue !== null && $issue !== '') {
            $fields['issue'] = $issue;
        }

        try {
            $item = $board->upsert((string) $this->argument('id'), $fields);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($item, JSON_UNESCAPED_SLASHES) ?: '{}');

        return self::SUCCESS;
    }
}
