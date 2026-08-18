<?php

namespace App\Factory;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Snapshot
{
    public function render(): string
    {
        $lines = [
            $this->catalogLine(),
            $this->lookLine(),
            $this->heartbeatLine(),
            $this->boardLine(),
            $this->gitLine(),
            'Snapshot is truth. Older chat or memory that disagrees is stale.',
        ];

        return implode("\n", $lines);
    }

    private function catalogLine(): string
    {
        $path = (string) config('kit.catalog_path');
        if ($path === '' || ! is_file($path)) {
            return '- catalog: missing';
        }

        $data = json_decode((string) File::get($path), true);
        $ids = [];
        foreach (($data['assets'] ?? []) as $row) {
            if (is_array($row) && isset($row['id'])) {
                $ids[] = $row['id'];
            }
        }

        return '- catalog ids: '.($ids === [] ? '(empty)' : implode(', ', $ids));
    }

    private function lookLine(): string
    {
        $path = (string) config('kit.look_report');
        if ($path === '' || ! is_file($path)) {
            return '- look report: none';
        }

        return '- look report: present ('.basename($path).')';
    }

    private function heartbeatLine(): string
    {
        $statusPath = (string) config('kit.status_path');
        if ($statusPath !== '' && is_file($statusPath)) {
            $status = json_decode((string) File::get($statusPath), true);
            $tools = is_array($status) ? ($status['tools'] ?? []) : [];
            if (is_array($tools) && $tools !== []) {
                $bits = [];
                foreach ($tools as $name => $state) {
                    $bits[] = $name.'='.$state;
                }

                return '- tools: '.implode(' ', $bits);
            }
        }

        try {
            $health = Http::timeout(2)->get((string) config('kit.kitd_health'));
            if ($health->successful()) {
                return '- kitd: up';
            }
        } catch (\Throwable) {
            // fall through
        }

        return '- kitd: unknown';
    }

    private function boardLine(): string
    {
        $path = (string) config('kit.board_path');
        if ($path === '' || ! is_file($path)) {
            return '- board: (none)';
        }

        $data = json_decode((string) File::get($path), true);
        $items = is_array($data) ? ($data['items'] ?? []) : [];
        $bits = [];
        foreach ($items as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $bit = $row['id'].'='.($row['state'] ?? '');
            if (! empty($row['lifecycle'])) {
                $bit .= '/'.$row['lifecycle'];
            }
            $bits[] = $bit;
        }

        return '- board: '.($bits === [] ? '(empty)' : implode('; ', $bits));
    }

    private function gitLine(): string
    {
        $root = (string) config('kit.bikes_v2');
        $head = $root.'/.git/HEAD';
        if (! is_file($head)) {
            return '- bikes-v2: (not found)';
        }

        $raw = trim((string) File::get($head));
        $branch = str_starts_with($raw, 'ref: ') ? basename($raw) : substr($raw, 0, 8);

        return '- bikes-v2 branch: '.$branch;
    }
}
