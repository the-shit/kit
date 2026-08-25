<?php

namespace App\Factory;

class VramOccupancy
{
    /**
     * Probe occupancy. Stub with config kit.gpu.fake_status in tests.
     * Never stops Forge. No beforeCut.
     *
     * @return array{gpu: 'blender'|'forge'|'ollama'|'free'}
     */
    public function status(): array
    {
        $fake = config('kit.gpu.fake_status');
        if (is_string($fake) && $fake !== '') {
            return ['gpu' => $this->normalize($fake)];
        }

        if ($this->blenderRunning()) {
            return ['gpu' => 'blender'];
        }

        if ($this->forgeListening()) {
            return ['gpu' => 'forge'];
        }

        return ['gpu' => 'free'];
    }

    private function normalize(string $raw): string
    {
        $gpu = strtolower(trim($raw));

        return in_array($gpu, ['blender', 'forge', 'ollama', 'free'], true) ? $gpu : 'free';
    }

    private function blenderRunning(): bool
    {
        exec('pgrep -x blender', $out, $code);

        return $code === 0;
    }

    private function forgeListening(): bool
    {
        $url = (string) config('kit.forge.url', 'http://127.0.0.1:7860');
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '127.0.0.1');
        $port = (int) (parse_url($url, PHP_URL_PORT) ?: 7860);
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.15);
        if (! is_resource($fp)) {
            return false;
        }
        fclose($fp);

        return true;
    }
}
