<?php

namespace App\Http\Controllers;

use App\Factory\Board;
use App\Jobs\DispatchKitWork;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asgard / Lexi factory door. No LLM. Enqueue + board row.
 *
 * POST /api/assign  Bearer KIT_PEER_TOKEN
 * { issue?, chair?, brief?, kind? }
 */
class AssignController extends Controller
{
    public function __invoke(Request $request, Board $board): JsonResponse
    {
        $expected = (string) config('kit.peer_token');
        $got = (string) $request->bearerToken();
        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $issue = trim((string) $request->input('issue', ''));
        $brief = trim((string) $request->input('brief', ''));
        $kind = strtolower(trim((string) $request->input('kind', '')));
        $chair = strtolower(trim((string) $request->input('chair', 'kit')));

        if (! in_array($chair, ['kit', 'bench', 'feel'], true)) {
            $chair = 'kit';
        }

        $issueNumber = $this->issueNumber($issue);
        if ($issue === '' && $brief === '' && $kind === '') {
            return response()->json(['error' => 'issue, brief, or kind required'], 422);
        }

        $boardId = $this->boardId($issueNumber, $kind, $brief, $issue);
        $state = $kind !== '' ? $kind : 'queued';
        $note = $brief !== '' ? $brief : ($issue !== '' ? $issue : $kind);

        $fields = [
            'state' => $state,
            'lifecycle' => 'queued',
            'owner' => $chair,
            'note' => $note,
        ];
        if ($issueNumber !== null) {
            $fields['issue'] = $issueNumber;
        }

        $item = $board->upsert($boardId, $fields);

        DispatchKitWork::dispatch(
            boardId: $boardId,
            chair: $chair,
            brief: $note,
            issue: $issue,
            kind: $kind,
        );

        return response()->json([
            'ok' => true,
            'board_id' => $boardId,
            'chair' => $chair,
            'item' => $item,
        ], 202);
    }

    private function issueNumber(string $issue): ?int
    {
        if ($issue === '') {
            return null;
        }
        if (preg_match('/#(\d+)/', $issue, $m) === 1) {
            return (int) $m[1];
        }
        if (preg_match('/\/issues\/(\d+)/', $issue, $m) === 1) {
            return (int) $m[1];
        }
        if (ctype_digit($issue)) {
            return (int) $issue;
        }

        return null;
    }

    private function boardId(?int $issueNumber, string $kind, string $brief, string $issue): string
    {
        $hay = strtolower($issue.' '.$brief.' '.$kind);
        foreach (['rider', 'hero-ebike', 'ranch-7620'] as $catalog) {
            if (str_contains($hay, $catalog)) {
                return $catalog;
            }
        }
        if ($issueNumber !== null) {
            return 'issue-'.$issueNumber;
        }
        if ($kind !== '') {
            return $kind;
        }

        return 'assign-'.substr(sha1($brief), 0, 8);
    }
}
