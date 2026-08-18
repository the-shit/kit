<?php

use App\Agent\PromptBuilder;

beforeEach(function () {
    config([
        'kit.catalog_path' => base_path('tests/fixtures/catalog.json'),
        'kit.look_report' => base_path('tests/fixtures/missing-look.json'),
        'kit.status_path' => base_path('tests/fixtures/missing-status.json'),
        'kit.board_path' => base_path('tests/fixtures/board.json'),
        'kit.bikes_v2' => base_path(),
        'kit.kitd_health' => 'http://127.0.0.1:9/health',
        'kit.memory_path' => storage_path('framework/testing/empty-memory.json'),
    ]);
    @unlink(storage_path('framework/testing/empty-memory.json'));
});

test('instructions include identity, rules, mission, and live catalog', function () {
    $text = app(PromptBuilder::class)->build('focus: hero-ebike');

    expect($text)
        ->toContain('You are Kit')
        ->toContain('Never socks')
        ->toContain('catalog ids: rider, hero-ebike')
        ->toContain('East Jan repair guy')
        ->toContain('focus: hero-ebike')
        ->toContain('not a Lexi citizen')
        ->toContain('board: ranch-7620=cut')
        ->toContain('Snapshot is truth');
});
