<?php

namespace App\Imaging;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ForgeClient
{
    public const CHECKPOINT = 'juggernautXL_v9';

    public const DENOISE_FACE = 0.2;

    public const DENOISE_STYLE = 0.35;

    public const DOWN = 'forge down (7860). Use backend=imagine.';

    public function txt2img(string $prompt, string $aspect, string $negative = ''): string
    {
        return $this->post('/sdapi/v1/txt2img', $this->payload($prompt, $aspect, $negative));
    }

    public function img2img(string $prompt, string $initBytes, string $aspect, float $denoise, string $negative = ''): string
    {
        if ($initBytes === '') {
            throw new RuntimeException('img2img needs an init image');
        }

        return $this->post('/sdapi/v1/img2img', [
            ...$this->payload($prompt, $aspect, $negative),
            'init_images' => [base64_encode($initBytes)],
            'denoising_strength' => $denoise,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listModels(): array
    {
        try {
            $response = $this->http()->get($this->url('/sdapi/v1/sd-models'));
            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array{width: int, height: int}
     */
    public function size(string $aspect): array
    {
        return match ($aspect) {
            '16:9' => ['width' => 1344, 'height' => 768],
            default => ['width' => 768, 'height' => 1024],
        };
    }

    public function denoiseFor(string $path): float
    {
        return str_contains(strtolower(basename($path)), 'sheet')
            ? self::DENOISE_FACE
            : self::DENOISE_STYLE;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(string $path, array $body): string
    {
        try {
            $response = $this->http()->post($this->url($path), $body);
        } catch (ConnectionException $e) {
            throw new RuntimeException(self::DOWN, 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('forge '.$path.' '.$response->status());
        }

        $b64 = $response->json('images.0');
        if (! is_string($b64) || $b64 === '') {
            throw new RuntimeException('forge returned no images');
        }

        $bytes = base64_decode($b64, true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('forge b64 decode failed');
        }

        return $bytes;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $prompt, string $aspect, string $negative): array
    {
        $size = $this->size($aspect);

        return [
            'prompt' => $prompt,
            'negative_prompt' => $negative,
            'width' => $size['width'],
            'height' => $size['height'],
            'steps' => 28,
            'cfg_scale' => 5.5,
            'sampler_name' => 'DPM++ 2M Karras',
            'seed' => -1,
            'do_not_save_samples' => true,
            'do_not_save_grid' => true,
            'override_settings_restore_afterwards' => false,
            'override_settings' => [
                'sd_model_checkpoint' => self::CHECKPOINT,
            ],
        ];
    }

    private function http(): PendingRequest
    {
        $timeout = (int) config('kit.forge.timeout', 180);

        return Http::timeout($timeout)->connectTimeout(5)->acceptJson()->asJson();
    }

    private function url(string $path): string
    {
        return rtrim((string) config('kit.forge.url', 'http://127.0.0.1:7860'), '/').$path;
    }
}
