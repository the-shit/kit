<?php

namespace App\Agent;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Thin laravel/ai agent. Pattern from the-shit/agent-skeleton.
 * Kit adds prompt layers in PromptBuilder — not Lexi's citizen stack.
 */
abstract class BaseAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /** @var array<int, array{role: string, content: string}> */
    private array $history = [];

    public static function make(): static
    {
        return app(static::class);
    }

    /** @param array<int, array{role: string, content: string}> $history */
    public function withHistory(array $history): static
    {
        $this->history = $history;

        return $this;
    }

    public function messages(): iterable
    {
        return array_map(
            fn (array $turn): AssistantMessage|UserMessage => $turn['role'] === 'assistant'
                ? new AssistantMessage($turn['content'])
                : new UserMessage($turn['content']),
            $this->history,
        );
    }

    abstract public function instructions(): Stringable|string;

    public function model(): string
    {
        return (string) config('kit.model', 'grok-4-fast');
    }

    public function tools(): iterable
    {
        return [];
    }
}
