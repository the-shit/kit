<?php

namespace App\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

readonly class YouTubeTranscript implements Tool
{
    public function description(): Stringable|string
    {
        return 'Fetch a YouTube transcript with timestamps via yt-dlp. Full captions, not an 8k clip. '
            .'Use when Jordan sends a technique video. Return takeaways yourself — do not dump raw VTT.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('YouTube URL.')->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $url = trim((string) ($request['url'] ?? ''));
        if ($url === '' || ! preg_match('/youtube\.com|youtu\.be/', $url)) {
            return 'url must be a YouTube link';
        }

        $bin = (string) config('kit.ytdlp');
        if ($bin === '' || ! is_file($bin)) {
            return 'yt-dlp missing at '.$bin;
        }

        $dir = storage_path('app/kit/transcripts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $out = $dir.'/%(id)s';
        $result = Process::timeout(90)->run([
            $bin, '--skip-download', '--write-auto-sub', '--write-sub',
            '--sub-lang', 'en.*', '--sub-format', 'vtt',
            '-o', $out, $url,
        ]);

        $files = glob($dir.'/*.vtt') ?: [];
        rsort($files);
        $vtt = $files[0] ?? null;
        if ($vtt === null || ! is_file($vtt)) {
            return 'no captions: '.trim($result->errorOutput() ?: $result->output());
        }

        $text = $this->vttToText((string) file_get_contents($vtt));
        if (strlen($text) > 24000) {
            $text = substr($text, 0, 24000)."\n… truncated at 24k chars";
        }

        return $text !== '' ? $text : 'captions empty';
    }

    private function vttToText(string $vtt): string
    {
        $lines = [];
        $last = '';
        $time = '';
        foreach (preg_split('/\R/', $vtt) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^(\d{2}:\d{2}:\d{2})\.\d+\s+-->/', $line, $m)) {
                $time = $m[1];
                continue;
            }
            if ($line === '' || str_starts_with($line, 'WEBVTT') || str_starts_with($line, 'NOTE') || preg_match('/^\d+$/', $line)) {
                continue;
            }
            $clean = html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($clean === '' || $clean === $last) {
                continue;
            }
            $lines[] = ($time !== '' ? "[{$time}] " : '').$clean;
            $last = $clean;
        }

        return implode("\n", $lines);
    }
}
