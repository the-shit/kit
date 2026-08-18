<?php

use App\Tools\CatalogRead;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    config(['kit.catalog_path' => base_path('tests/fixtures/catalog.json')]);
});

test('lists catalog ids', function () {
    $out = (string) (new CatalogRead)->handle(new Request([]));

    expect($out)
        ->toContain('rider → /models/rider/rider.glb')
        ->toContain('hero-ebike → /models/bike/hero-ebike.glb');
});

test('returns one row', function () {
    $out = (string) (new CatalogRead)->handle(new Request(['id' => 'rider']));

    expect($out)->toContain('"id": "rider"')->toContain('/models/rider/rider.glb');
});

test('missing id', function () {
    $out = (string) (new CatalogRead)->handle(new Request(['id' => 'nope']));

    expect($out)->toBe('catalog: missing nope');
});
