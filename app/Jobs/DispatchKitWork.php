<?php

namespace App\Jobs;

use App\Mattermost\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Async factory hop after /api/assign. No LLM.
 */
class DispatchKitWork implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $boardId,
        public readonly string $chair,
        public readonly string $brief,
        public readonly string $issue,
        public readonly string $kind,
    ) {
        $this->onQueue('kit');
    }

    public function handle(Client $mm): void
    {
        $hallway = (string) config('kit.mattermost.hallway_id');
        $bits = ['_asgard assign_'];
        $bits[] = 'board=`'.$this->boardId.'`';
        $bits[] = 'chair='.$this->chair;
        if ($this->kind !== '') {
            $bits[] = 'kind='.$this->kind;
        }
        if ($this->issue !== '') {
            $bits[] = $this->issue;
        }
        if ($this->brief !== '') {
            $bits[] = $this->brief;
        }

        $dm = (string) config('kit.mattermost.dm_id');
        if ($hallway === '' || $hallway === $dm) {
            return;
        }

        $mm->post($hallway, implode(' · ', $bits));
    }
}
