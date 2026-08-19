<?php

namespace App\Imaging;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ImagineClient
{
    /**
     * @param  list<string>  $paths
     */
    public function edit(string $prompt, array $paths, string $aspect, string $resolution, string $model): string
    {
        if ($paths === []) {
            throw new RuntimeException('edit needs at least one image');
        }

        $images = [];
        foreach (array_slice($paths, 0, 3) as $path) {
            $bin = (string) file_get_contents($path);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $images[] = ['url' => 'data:'.$mime.';base64,'.base64_encode($bin)];
        }

        return $this->post('/images/edits', [
            'model' => $model,
            'prompt' => $prompt,
            'images' => $images,
            'aspect_ratio' => $aspect,
            'resolution' => $this->resolution($resolution),
            'response_format' => 'b64_json',
        ]);
    }

    public function generate(string $prompt, string $aspect, string $resolution, string $model): string
    {
        return $this->post('/images/generations', [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'aspect_ratio' => $aspect,
            'resolution' => $this->resolution($resolution),
            'response_format' => 'b64_json',
        ]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(string $path, array $body): string
    {
        $key = (string) config('ai.providers.xai.key');
        if ($key === '') {
            throw new RuntimeException('XAI_API_KEY empty');
        }

        $base = rtrim((string) config('kit.imagine.base', 'https://api.x.ai/v1'), '/');
        $timeout = (int) config('kit.imagine.timeout', 60);
        $response = Http::withToken($key)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($base.$path, $body);

        if (! $response->successful()) {
            throw new RuntimeException('imagine '.$path.' '.$response->status().' '.$response->body());
        }

        $b64 = $response->json('data.0.b64_json');
        if (! is_string($b64) || $b64 === '') {
            throw new RuntimeException('imagine missing b64_json');
        }

        $bytes = base64_decode($b64, true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('imagine b64 decode failed');
        }

        return $bytes;
    }

    public function resolution(string $raw): string
    {
        $n = strtolower(trim($raw));

        return in_array($n, ['1k', '2k'], true) ? $n : '1k';
    }
}
