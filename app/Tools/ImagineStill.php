<?php

namespace App\Tools;

use App\Factory\Board;
use App\Imaging\ImagineClient;
use App\Imaging\RefStore;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

readonly class ImagineStill implements Tool
{
    public function description(): Stringable|string
    {
        return 'Edit a rider factory still onto bikes-v2 disk (_wip only). '
            .'v1: catalog_id=rider, view=front|side|back|ride. '
            .'Reference-first Imagine edit (Jordan photos). No VRAM. '
            .'Does not cut a GLB, HTTP Lexi, touch board id=rider, or overwrite sheet/hero. '
            .'One stills call per turn. Do not pair with BlenderRun, LookCompare, or AskLexi.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'catalog_id' => $schema->string()->description('Must be rider in v1.')->required(),
            'view' => $schema->string()->description('front|side|back|ride')->required(),
            'issue' => $schema->integer()->description('bikes-v2 SKU issue (12 for rider). Tool ticket is kit#5.')->required(),
            'prompt' => $schema->string()->description('Camera/lighting only. Body lock is injected.'),
            'backend' => $schema->string()->description('imagine only in I2. forge is I4.'),
            'mode' => $schema->string()->description('edit (default) | gen. gen on rider requires force_fresh.'),
            'ref_paths' => $schema->array()->description('Optional extra filenames under tools/models/rider/refs/. Max 3 with the canonical.'),
            'replace_canonical' => $schema->boolean()->description('v1 ignored / refuse. Always write _wip/.'),
            'model' => $schema->string()->description('grok-imagine-image-2.0 (default) | grok-imagine-image | grok-imagine-image-quality.'),
            'resolution' => $schema->string()->description('1k (default) | 2k. Lowercase tokens only.'),
            'aspect_ratio' => $schema->string()->description('Override. Default 3:4 portraits / 16:9 ride.'),
            'force_fresh' => $schema->boolean()->description('Allow /generations on rider. Default false. Refuse unless true.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            return $this->run($request);
        } catch (Throwable $e) {
            return 'ImagineStill: '.$e->getMessage();
        }
    }

    private function run(Request $request): string
    {
        $id = strtolower(trim((string) ($request['catalog_id'] ?? '')));
        if ($id !== 'rider') {
            if (str_starts_with($id, 'logo')) {
                return 'logos are SVG/code, not ImagineStill';
            }

            return 'v1 catalog_id must be rider (hero-ebike refs live under tools/models/bike/refs/; ranch-7620 has no tools refs tree)';
        }

        $view = strtolower(trim((string) ($request['view'] ?? '')));
        $store = app(RefStore::class);
        $map = $store->map($view);
        if ($map === null) {
            return 'view must be front|side|back|ride';
        }

        $issue = (int) ($request['issue'] ?? 0);
        if ($issue <= 0) {
            return 'issue required (bikes-v2 #12 for rider)';
        }

        $backend = strtolower(trim((string) ($request['backend'] ?? 'imagine')));
        if ($backend !== '' && $backend !== 'imagine') {
            return 'I2 backend=imagine only (forge is I4)';
        }

        $mode = strtolower(trim((string) ($request['mode'] ?? 'edit')));
        $force = (bool) ($request['force_fresh'] ?? false);
        if ($mode === 'gen' && ! $force) {
            return 'rider gen refused without force_fresh';
        }
        if ($mode !== 'gen' || $force === false) {
            $mode = 'edit';
        }

        $client = app(ImagineClient::class);
        $model = trim((string) ($request['model'] ?? '')) ?: (string) config('kit.imagine.default_model', 'grok-imagine-image-2.0');
        $resolution = $client->resolution((string) ($request['resolution'] ?? '1k'));
        $aspect = trim((string) ($request['aspect_ratio'] ?? '')) ?: $map['aspect'];
        $camera = trim((string) ($request['prompt'] ?? ''));
        $extras = $this->names($request['ref_paths'] ?? []);
        $paths = $store->resolve($view, $extras);
        $prompt = $this->lockPrompt($view, count($paths), $camera);
        $before = $store->protectHashes();

        $bytes = $mode === 'gen'
            ? $client->generate($prompt, $aspect, $resolution, $model)
            : $client->edit($prompt, $paths, $aspect, $resolution, $model);

        $written = $store->writeWip($view, $bytes, [
            'backend' => 'imagine',
            'model' => $model,
            'issue' => $issue,
            'refs_used' => array_map('basename', $paths),
        ]);

        if (! $store->hashesUnchanged($before)) {
            @unlink($written['path']);
            @unlink($written['sidecar']);

            return 'aborted: sheet/hero hash changed';
        }

        app(Board::class)->upsert('rider-stills', [
            'state' => $view,
            'issue' => $issue,
            'note' => 'stills #'.$issue.' '.$view.' wip='.basename($written['path']),
        ]);

        return json_encode([
            'path' => $written['path'],
            'sha256' => $written['sha256'],
            'bytes' => $written['bytes'],
            'backend' => 'imagine',
            'model' => $model,
            'issue' => $issue,
            'refs_used' => array_map('basename', $paths),
        ], JSON_UNESCAPED_SLASHES) ?: 'wrote still';
    }

    private function lockPrompt(string $view, int $n, string $camera): string
    {
        $lock = "Same man, 6'4 320, flip-flops, bare feet, shorts, tee, no socks, no helmet. "
            .ucfirst($view).', even studio light.';
        if ($n >= 2) {
            $lock .= ' <IMAGE_0> is the '.$view.' still — keep that pose, clothes, and camera.'
                .' <IMAGE_1> is the face sheet — keep that face (glasses, beard).';
        }
        if ($n >= 3) {
            $lock .= ' <IMAGE_2> is the hero taste lock — tall Pixar-cartoon, modest gut, flops.';
        }
        if ($camera !== '') {
            $lock .= ' '.$camera;
        }

        return $lock;
    }

    /**
     * @return list<string>
     */
    private function names(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $name) {
            if (is_string($name) && $name !== '') {
                $out[] = $name;
            }
        }

        return $out;
    }
}
