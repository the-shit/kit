<?php

use App\Factory\VramOccupancy;

test('fake_status is stubbable without live blender or forge', function (string $gpu) {
    config(['kit.gpu.fake_status' => $gpu]);

    expect(app(VramOccupancy::class)->status())->toBe(['gpu' => $gpu]);
})->with(['blender', 'forge', 'ollama', 'free']);

test('unknown fake_status falls back to free', function () {
    config(['kit.gpu.fake_status' => 'trellis']);

    expect(app(VramOccupancy::class)->status())->toBe(['gpu' => 'free']);
});

test('class has status and no beforeCut', function () {
    $methods = get_class_methods(VramOccupancy::class);

    expect($methods)->toContain('status');
    expect($methods)->not->toContain('beforeCut');
});
