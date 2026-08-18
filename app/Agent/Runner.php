<?php

namespace App\Agent;

use App\Memory\ConversationStore;
use Throwable;

class Runner
{
    public function __construct(private readonly ConversationStore $conversations) {}

    public function say(string $thread, string $message): string
    {
        $history = $this->conversations->history($thread);
        $this->conversations->push($thread, 'user', $message);

        try {
            $reply = KitAgent::make()
                ->withHistory($history)
                ->prompt($message)
                ->text;
        } catch (Throwable $e) {
            $reply = 'Kit brain failed: '.$e->getMessage();
        }

        $this->conversations->push($thread, 'assistant', $reply);

        return $reply;
    }
}
