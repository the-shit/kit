<?php

namespace App\Console\Commands;

use App\Agent\PromptBuilder;
use App\Agent\Runner;
use Illuminate\Console\Command;

class AskCommand extends Command
{
    protected $signature = 'kit:ask {message?* : What to ask Kit} {--prompt : Print assembled instructions and exit}';

    protected $description = 'Talk to Kit on the CLI (or dump the system prompt)';

    public function handle(Runner $runner, PromptBuilder $prompts): int
    {
        if ($this->option('prompt')) {
            $this->line($prompts->build());

            return self::SUCCESS;
        }

        $message = trim(implode(' ', $this->argument('message')));
        if ($message === '') {
            $this->error('message required');

            return self::FAILURE;
        }

        $this->line($runner->say('cli', $message));

        return self::SUCCESS;
    }
}
