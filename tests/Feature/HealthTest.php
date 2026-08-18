<?php

test('health is open', function () {
    $this->get('/health')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'peer' => 'kit',
            'sdk' => 'laravel/ai',
            'lexi' => 'sibling',
        ]);
});

test('api health matches', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('host', 'loki');
});
