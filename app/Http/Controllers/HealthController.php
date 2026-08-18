<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'peer' => 'kit',
            'host' => 'loki',
            'sdk' => 'laravel/ai',
            'lexi' => 'sibling',
        ]);
    }
}
