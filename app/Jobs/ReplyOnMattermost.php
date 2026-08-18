<?php

namespace App\Jobs;

use App\Agent\Runner;
use App\Mattermost\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReplyOnMattermost implements ShouldQueue
{
    use Queueable;

    public int $timeout = 200;

    public int $tries = 2;

    public function __construct(
        public string $channel,
        public string $message,
    ) {
        $this->onQueue('kit');
    }

    public function handle(Runner $runner, Client $mattermost): void
    {
        $thread = $this->channel !== '' ? $this->channel : 'mm';
        $reply = $runner->say($thread, $this->message);
        $mattermost->post($this->channel, $reply);
    }
}
