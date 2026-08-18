<?php

namespace App\Http\Controllers;

use App\Agent\Runner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AskController extends Controller
{
    public function __invoke(Request $request, Runner $runner): JsonResponse
    {
        $expected = (string) config('kit.peer_token');
        $got = (string) $request->bearerToken();
        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json(['error' => 'message required'], 422);
        }

        $thread = (string) $request->input('thread', 'peer');
        $text = $runner->say($thread, $message);

        return response()->json(['ok' => true, 'text' => $text]);
    }
}
