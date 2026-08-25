<?php

use App\Imaging\ForgeClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'kit.forge.url' => 'http://127.0.0.1:7860',
        'kit.forge.timeout' => 180,
        'kit.gpu.fake_status' => 'free',
    ]);
});

test('txt2img decodes images0 and never sends another checkpoint', function () {
    Http::fake([
        'http://127.0.0.1:7860/sdapi/v1/txt2img' => function (Request $request) {
            $body = $request->data();
            expect($body['do_not_save_samples'])->toBeTrue()
                ->and($body['do_not_save_grid'])->toBeTrue()
                ->and($body['override_settings_restore_afterwards'])->toBeFalse()
                ->and($body['override_settings']['sd_model_checkpoint'])->toBe('juggernautXL_v9')
                ->and($body['override_settings'])->toHaveCount(1)
                ->and($body['width'])->toBe(1344)
                ->and($body['height'])->toBe(768)
                ->and($body['steps'])->toBe(28)
                ->and($body['cfg_scale'])->toBe(5.5)
                ->and($body['sampler_name'])->toBe('DPM++ 2M Karras');

            return Http::response([
                'images' => [base64_encode('PNGBYTES')],
            ], 200);
        },
    ]);

    $bytes = app(ForgeClient::class)->txt2img('a rider', '16:9');

    expect($bytes)->toBe('PNGBYTES');
});

test('img2img face sheet uses denoise 0.2', function () {
    Http::fake([
        'http://127.0.0.1:7860/sdapi/v1/img2img' => function (Request $request) {
            expect($request->data()['denoising_strength'])->toBe(0.2);

            return Http::response([
                'images' => [base64_encode('FACE')],
            ], 200);
        },
    ]);

    $client = app(ForgeClient::class);
    $denoise = $client->denoiseFor('/tmp/jordan-sheet.png');
    $bytes = $client->img2img('same face', 'init', '3:4', $denoise);

    expect($denoise)->toBe(0.2)->and($bytes)->toBe('FACE');
});

test('connection refused is the forge-down string', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    expect(fn () => app(ForgeClient::class)->txt2img('x', '3:4'))
        ->toThrow(RuntimeException::class, ForgeClient::DOWN);
});

test('listModels returns empty on failure', function () {
    Http::fake(function () {
        throw new ConnectionException('down');
    });

    expect(app(ForgeClient::class)->listModels())->toBe([]);
});
