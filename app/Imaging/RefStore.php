<?php

namespace App\Imaging;

use RuntimeException;

class RefStore
{
    /** @var list<string> */
    public const VIEWS = ['front', 'side', 'back', 'ride'];

    /** @var list<string> */
    public const PROTECTED = ['jordan-sheet.png', 'jordan-hero.jpg'];

    /**
     * @return array{canonical: string, extras: list<string>, aspect: string}|null
     */
    public function map(string $view): ?array
    {
        return match ($view) {
            'front' => ['canonical' => 'jordan-front-flops.jpg', 'extras' => ['jordan-sheet.png', 'jordan-hero.jpg'], 'aspect' => '3:4'],
            'side' => ['canonical' => 'jordan-side-flops.jpg', 'extras' => ['jordan-sheet.png', 'jordan-hero.jpg'], 'aspect' => '3:4'],
            'back' => ['canonical' => 'jordan-back-flops.jpg', 'extras' => ['jordan-sheet.png', 'jordan-hero.jpg'], 'aspect' => '3:4'],
            'ride' => ['canonical' => 'jordan-ride-flops.jpg', 'extras' => ['jordan-sheet.png', 'jordan-hero.jpg'], 'aspect' => '16:9'],
            default => null,
        };
    }

    public function refsRoot(): string
    {
        return rtrim((string) config('kit.bikes_v2'), '/').'/tools/models/rider/refs';
    }

    public function wipDir(): string
    {
        return $this->refsRoot().'/_wip';
    }

    /**
     * @param  list<string>  $extraNames
     * @return list<string>
     */
    public function resolve(string $view, array $extraNames = []): array
    {
        $map = $this->map($view);
        if ($map === null) {
            throw new RuntimeException('view must be front|side|back|ride');
        }

        $root = $this->refsRoot();
        $names = [$map['canonical']];
        $extras = $extraNames !== [] ? $extraNames : $map['extras'];
        foreach ($extras as $name) {
            $name = basename((string) $name);
            if ($name === '' || $name === $map['canonical']) {
                continue;
            }
            if (! $this->allowlisted($name)) {
                throw new RuntimeException('ref not allowlisted: '.$name);
            }
            $names[] = $name;
            if (count($names) >= 3) {
                break;
            }
        }

        $paths = [];
        foreach ($names as $name) {
            $path = $root.'/'.$name;
            if (! is_file($path)) {
                throw new RuntimeException('missing ref '.$name);
            }
            $paths[] = $path;
        }

        return $paths;
    }

    public function allowlisted(string $name): bool
    {
        $name = basename($name);

        return (bool) preg_match('/^jordan-[a-z0-9-]+\.(jpg|jpeg|png)$/', $name);
    }

    /**
     * @return array<string, string>
     */
    public function protectHashes(): array
    {
        $hashes = [];
        $roots = [
            $this->refsRoot(),
            rtrim((string) config('kit.bikes_v2'), '/').'/public/models/rider/refs',
        ];
        foreach ($roots as $root) {
            foreach (self::PROTECTED as $name) {
                $path = $root.'/'.$name;
                if (is_file($path)) {
                    $hashes[$path] = hash_file('sha256', $path) ?: '';
                }
            }
        }

        return $hashes;
    }

    /**
     * @param  array<string, string>  $before
     */
    public function hashesUnchanged(array $before): bool
    {
        $after = $this->protectHashes();
        foreach ($before as $path => $hash) {
            if (($after[$path] ?? '') !== $hash) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{path: string, sha256: string, sidecar: string, bytes: int}
     */
    public function writeWip(string $view, string $bytes, array $meta): array
    {
        if ($bytes === '') {
            throw new RuntimeException('empty still');
        }
        $dir = $this->wipDir();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('cannot create '.$dir);
        }

        $sha = hash('sha256', $bytes);
        $name = now('America/Phoenix')->format('Ymd').'-'.$view.'-'.substr($sha, 0, 8).'.jpg';
        $path = $dir.'/'.$name;
        $realDir = realpath($dir);
        if ($realDir === false || ! str_starts_with($path, $realDir)) {
            throw new RuntimeException('wip path escaped');
        }
        if (file_put_contents($path, $bytes) === false) {
            throw new RuntimeException('wip write failed');
        }

        $sidecar = substr($path, 0, -4).'.kit.json';
        $payload = array_merge($meta, [
            'sha256' => $sha,
            'view' => $view,
            'created' => now('America/Phoenix')->toIso8601String(),
            'bytes' => strlen($bytes),
        ]);
        file_put_contents($sidecar, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return ['path' => $path, 'sha256' => $sha, 'sidecar' => $sidecar, 'bytes' => strlen($bytes)];
    }
}
