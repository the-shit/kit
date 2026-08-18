<?php

use App\Memory\FileMemory;

beforeEach(function () {
    $path = storage_path('framework/testing/kit-memory.json');
    @unlink($path);
    config(['kit.memory_path' => $path]);
});

test('store and search memory', function () {
    $mem = app(FileMemory::class);
    $mem->store('rider is M1-locked', ['rider', 'lock']);
    $mem->store('next mesh is hero-ebike', ['ebike']);

    $hits = $mem->search('ebike');
    expect($hits)->toHaveCount(1)
        ->and($hits[0]['text'])->toBe('next mesh is hero-ebike');

    expect($mem->render(2))->toContain('M1-locked');
});
