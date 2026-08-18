<?php

namespace App\Factory;

use RuntimeException;

class Board
{
    /** @var list<string> */
    public const LIFECYCLES = ['queued', 'wip', 'cut', 'pr', 'live', 'blocked'];

    public function path(): string
    {
        $path = (string) config('kit.board_path');
        if ($path === '') {
            throw new RuntimeException('kit.board_path empty');
        }

        return $path;
    }

    /**
     * @return array{updated: string, items: list<array<string, mixed>>}
     */
    public function read(): array
    {
        return $this->locked(function ($fh): array {
            return $this->decode($this->readHandle($fh));
        }, LOCK_SH);
    }

    /**
     * Merge one row by id. state is a free string. lifecycle if set must be known.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function upsert(string $id, array $fields): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new RuntimeException('board id required');
        }

        return $this->locked(function ($fh) use ($id, $fields): array {
            $data = $this->decode($this->readHandle($fh));
            $items = $data['items'];
            $idx = null;
            foreach ($items as $i => $row) {
                if (($row['id'] ?? '') === $id) {
                    $idx = $i;
                    break;
                }
            }
            $base = $idx === null ? ['id' => $id] : $items[$idx];
            $item = $this->merge($base, $fields);
            if ($idx === null) {
                $items[] = $item;
            } else {
                $items[$idx] = $item;
            }
            $payload = [
                'updated' => now('America/Phoenix')->toIso8601String(),
                'items' => array_values($items),
            ];
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
            rewind($fh);
            ftruncate($fh, 0);
            fwrite($fh, $json);

            return $item;
        }, LOCK_EX);
    }

    /**
     * Heartbeat slim: id + state + lifecycle if present.
     *
     * @return array{updated: string, items: list<array<string, mixed>>}
     */
    public function slim(): array
    {
        $data = $this->read();
        $items = [];
        foreach ($data['items'] as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $slim = ['id' => $row['id'], 'state' => $row['state'] ?? ''];
            if (isset($row['lifecycle']) && $row['lifecycle'] !== null && $row['lifecycle'] !== '') {
                $slim['lifecycle'] = $row['lifecycle'];
            }
            $items[] = $slim;
        }

        return ['updated' => $data['updated'], 'items' => $items];
    }

    /**
     * @template T
     *
     * @param  callable(resource): T  $fn
     * @return T
     */
    private function locked(callable $fn, int $lock)
    {
        $path = $this->path();
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('cannot create '.$dir);
        }
        $fh = fopen($path, 'c+');
        if ($fh === false) {
            throw new RuntimeException('cannot open '.$path);
        }
        try {
            if (! flock($fh, $lock)) {
                throw new RuntimeException('board flock failed');
            }

            return $fn($fh);
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /** @param resource $fh */
    private function readHandle($fh): string
    {
        rewind($fh);
        $raw = stream_get_contents($fh);

        return $raw === false ? '' : $raw;
    }

    /**
     * @return array{updated: string, items: list<array<string, mixed>>}
     */
    private function decode(string $raw): array
    {
        $data = $raw === '' ? [] : json_decode($raw, true);
        $items = is_array($data) ? ($data['items'] ?? []) : [];
        if (! is_array($items)) {
            $items = [];
        }

        return [
            'updated' => is_array($data) ? (string) ($data['updated'] ?? '') : '',
            'items' => array_values(array_filter($items, 'is_array')),
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function merge(array $base, array $fields): array
    {
        foreach (['state', 'owner', 'pr', 'note'] as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] !== null) {
                $base[$key] = is_string($fields[$key]) ? trim($fields[$key]) : $fields[$key];
            }
        }
        if (array_key_exists('lifecycle', $fields)) {
            $life = $fields['lifecycle'];
            if ($life === null || $life === '') {
                $base['lifecycle'] = null;
            } else {
                $life = trim((string) $life);
                if (! in_array($life, self::LIFECYCLES, true)) {
                    throw new RuntimeException('lifecycle must be queued|wip|cut|pr|live|blocked');
                }
                $base['lifecycle'] = $life;
            }
        }
        if (array_key_exists('issue', $fields) && $fields['issue'] !== null && $fields['issue'] !== '') {
            $base['issue'] = (int) $fields['issue'];
        }
        if (array_key_exists('hops', $fields) && $fields['hops'] !== null && $fields['hops'] !== '') {
            $base['hops'] = (int) $fields['hops'];
        }

        return $base;
    }
}
