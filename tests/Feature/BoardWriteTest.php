<?php

use App\Factory\Board;
use App\Tools\BoardWrite;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $path = storage_path('framework/testing/board-'.uniqid('', true).'.json');
    @unlink($path);
    config(['kit.board_path' => $path]);
    $this->boardPath = $path;
});

afterEach(function () {
    @unlink($this->boardPath);
});

test('upserts a row and slims id state lifecycle', function () {
    $out = (string) (new BoardWrite)->handle(new Request([
        'id' => 'ranch-7620',
        'state' => 'cut',
        'lifecycle' => 'cut',
        'note' => 'chain on +X',
    ]));

    expect($out)->toContain('"id": "ranch-7620"')->toContain('"state": "cut"');

    $slim = app(Board::class)->slim();
    expect($slim['items'])->toHaveCount(1)
        ->and($slim['items'][0])->toMatchArray([
            'id' => 'ranch-7620',
            'state' => 'cut',
            'lifecycle' => 'cut',
        ]);
});

test('state stays a free string', function () {
    (new BoardWrite)->handle(new Request([
        'id' => 'next',
        'state' => 'hero-ebike',
    ]));

    expect(app(Board::class)->read()['items'][0]['state'])->toBe('hero-ebike');
});

test('rejects a closed-enum lifecycle typo', function () {
    $out = (string) (new BoardWrite)->handle(new Request([
        'id' => 'ranch-7620',
        'state' => 'cut',
        'lifecycle' => 'shipping',
    ]));

    expect($out)->toContain('lifecycle must be');
});

test('heartbeat slim replica sees a write', function () {
    app(Board::class)->upsert('blender', ['state' => 'cli']);
    $slim = app(Board::class)->slim();

    expect(json_encode($slim))->toContain('"id":"blender"')->toContain('"state":"cli"');
});

test('two processes writing different ids do not tear the json', function () {
    $path = $this->boardPath;
    $artisan = base_path('artisan');
    $php = PHP_BINARY;
    putenv('KIT_BOARD_PATH='.$path);
    $_ENV['KIT_BOARD_PATH'] = $path;
    $procs = [];
    foreach (['alpha' => 'one', 'beta' => 'two'] as $id => $state) {
        $cmd = sprintf(
            '%s %s kit:board %s %s',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($id),
            escapeshellarg($state),
        );
        $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
    }
    foreach ($procs as $proc) {
        if (is_resource($proc)) {
            proc_close($proc);
        }
    }

    $data = json_decode((string) file_get_contents($path), true);
    $ids = array_column($data['items'] ?? [], 'id');
    sort($ids);
    expect($ids)->toBe(['alpha', 'beta']);
});
