<?php

namespace App\Agent;

use App\Tools\AskLexi;
use App\Tools\BoardWrite;
use App\Tools\CatalogRead;
use App\Tools\HeartbeatRead;
use App\Tools\LookReport;
use App\Tools\MemorySearch;
use App\Tools\MemoryStore;
use App\Tools\YouTubeTranscript;
use Laravel\Ai\Attributes\Timeout;
use Stringable;

#[Timeout(200)]
class KitAgent extends BaseAgent
{
    public function __construct(private readonly PromptBuilder $prompts) {}

    public function instructions(): Stringable|string
    {
        return $this->prompts->build();
    }

    public function tools(): iterable
    {
        return [
            new CatalogRead,
            new HeartbeatRead,
            new BoardWrite,
            new LookReport,
            new YouTubeTranscript,
            new MemorySearch,
            new MemoryStore,
            new AskLexi,
        ];
    }
}
