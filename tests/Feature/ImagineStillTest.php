<?php

use App\Agent\KitAgent;
use App\Factory\Board;
use App\Tools\AskLexi;
use App\Tools\ImagineStill;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

function kitTinyJpeg(): string
{
    $raw = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    return base64_decode($raw, true) ?: 'JPEG';
}

function kitTinyPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true) ?: 'PNG';
}

function kitRiderTree(): string
{
    $root = storage_path('framework/testing/rider-'.uniqid('', true));
    $refs = $root.'/tools/models/rider/refs';
    $public = $root.'/public/models/rider/refs';
    mkdir($refs, 0755, true);
    mkdir($public, 0755, true);
    $jpeg = kitTinyJpeg();
    $png = kitTinyPng();
    foreach (['jordan-front-flops.jpg', 'jordan-side-flops.jpg', 'jordan-back-flops.jpg', 'jordan-ride-flops.jpg', 'jordan-hero.jpg'] as $name) {
        file_put_contents($refs.'/'.$name, $jpeg);
        file_put_contents($public.'/'.$name, $jpeg);
    }
    file_put_contents($refs.'/jordan-sheet.png', $png);
    file_put_contents($public.'/jordan-sheet.png', $png);

    return $root;
}

beforeEach(function () {
    $this->bikes = kitRiderTree();
    config([
        'kit.bikes_v2' => $this->bikes,
        'kit.board_path' => storage_path('framework/testing/board-'.uniqid('', true).'.json'),
        'ai.providers.xai.key' => 'test-xai',
        'kit.imagine.base' => 'https://api.x.ai/v1',
        'kit.forge.url' => 'http://127.0.0.1:7860',
        'kit.gpu.fake_status' => 'free',
    ]);
    app(Board::class)->upsert('rider', [
        'state' => 'm1',
        'note' => 'look-compare locked. do not reopen clothes.',
    ]);
});

afterEach(function () {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->bikes, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    @rmdir($this->bikes);
    @unlink((string) config('kit.board_path'));
});

test('kit carries ImagineStill and AskLexi', function () {
    $classes = array_map(fn ($tool) => $tool::class, iterator_to_array(app(KitAgent::class)->tools(), false));

    expect($classes)->toContain(ImagineStill::class)->toContain(AskLexi::class);
});

test('unknown catalog id errors', function () {
    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'hero-ebike',
        'view' => 'front',
        'issue' => 12,
    ]));

    expect($out)->toContain('v1 catalog_id must be rider');
});

test('rider without force_fresh does not call generations', function () {
    Http::fake([
        'https://api.x.ai/v1/images/edits' => Http::response([
            'data' => [['b64_json' => base64_encode(kitTinyJpeg())]],
        ], 200),
        'https://api.x.ai/v1/images/generations' => Http::response(['nope' => true], 500),
    ]);

    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'rider',
        'view' => 'front',
        'issue' => 12,
        'mode' => 'gen',
    ]));

    expect($out)->toContain('gen refused');
    Http::assertNothingSent();
});

test('three-ref edit writes wip and cites IMAGE_1', function () {
    Http::fake(function (Illuminate\Http\Client\Request $request) {
        expect($request->url())->toEndWith('/images/edits');
        $body = $request->data();
        expect($body['prompt'])->toContain('<IMAGE_1>')
            ->and($body['images'])->toHaveCount(3)
            ->and($body['images'][0])->toHaveKey('url')
            ->and($body['resolution'])->toBe('1k')
            ->and($body)->not->toHaveKey('image');

        return Http::response([
            'data' => [['b64_json' => base64_encode(kitTinyJpeg())]],
        ], 200);
    });

    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'rider',
        'view' => 'front',
        'issue' => 12,
        'prompt' => 'soft window light',
    ]));

    expect($out)->toContain('_wip/')->toContain('"issue":12');
    $wip = $this->bikes.'/tools/models/rider/refs/_wip';
    expect(glob($wip.'/*.jpg'))->not->toBeEmpty();

    $board = app(Board::class)->read();
    $ids = array_column($board['items'], 'id');
    expect($ids)->toContain('rider-stills')->toContain('rider');
    $rider = collect($board['items'])->firstWhere('id', 'rider');
    expect($rider['note'])->toContain('do not reopen clothes');
});

test('forge up img2img writes wip with juggernaut payload', function () {
    config(['kit.gpu.fake_status' => 'forge']);

    Http::fake([
        'http://127.0.0.1:7860/*' => function (Illuminate\Http\Client\Request $request) {
            expect($request->url())->toEndWith('/sdapi/v1/img2img');
            $body = $request->data();
            expect($body['do_not_save_samples'])->toBeTrue()
                ->and($body['do_not_save_grid'])->toBeTrue()
                ->and($body['override_settings_restore_afterwards'])->toBeFalse()
                ->and($body['override_settings']['sd_model_checkpoint'])->toBe('juggernautXL_v9')
                ->and($body['override_settings'])->toHaveCount(1)
                ->and($body['denoising_strength'])->toBe(0.35)
                ->and($body['width'])->toBe(768)
                ->and($body['height'])->toBe(1024)
                ->and($body['steps'])->toBe(28)
                ->and($body['cfg_scale'])->toBe(5.5)
                ->and($body['sampler_name'])->toBe('DPM++ 2M Karras');

            return Http::response([
                'images' => [base64_encode(kitTinyJpeg())],
            ], 200);
        },
        'https://api.x.ai/v1/*' => Http::response(['nope' => true], 500),
    ]);

    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'rider',
        'view' => 'front',
        'issue' => 12,
        'backend' => 'forge',
    ]));

    expect($out)->toContain('_wip/')
        ->and($out)->toContain('"backend":"forge"')
        ->and($out)->toContain('juggernautXL_v9');

    $wip = $this->bikes.'/tools/models/rider/refs/_wip';
    expect(glob($wip.'/*.jpg'))->not->toBeEmpty();
    $sidecars = glob($wip.'/*.kit.json');
    expect($sidecars)->not->toBeEmpty();
    $meta = json_decode((string) file_get_contents($sidecars[0]), true);
    expect($meta['backend'])->toBe('forge')
        ->and($meta['model'])->toBe('juggernautXL_v9');
});

test('blender busy refuses forge without HTTP', function () {
    config(['kit.gpu.fake_status' => 'blender']);
    Http::fake();

    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'rider',
        'view' => 'front',
        'issue' => 12,
        'backend' => 'forge',
    ]));

    expect($out)->toBe('blender in flight — retry forge stills after the cut (no HTTP)');
    Http::assertNothingSent();
});

test('forge down surfaces 7860 string', function () {
    config(['kit.gpu.fake_status' => 'free']);
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $out = (string) (new ImagineStill)->handle(new Request([
        'catalog_id' => 'rider',
        'view' => 'front',
        'issue' => 12,
        'backend' => 'forge',
    ]));

    expect($out)->toContain('forge down (7860)');
});
