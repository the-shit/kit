<?php

namespace App\Http\Controllers;

use App\Factory\Board;
use App\Mattermost\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignController extends Controller
{
    public function __invoke(Request $request, Board $board, Client $mattermost): JsonResponse
    {
        $expected = (string) config('kit.peer_token');
        $got = (string) $request->bearerToken();
        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $issue = trim((string) $request->input('issue', ''));
        if ($issue === '') {
            return response()->json(['error' => 'issue required'], 422);
        }

        $chair = strtolower(trim((string) $request->input('chair', 'kit')));
        if (! in_array($chair, ['kit', 'feel', 'bench'], true)) {
            $chair = 'kit';
        }

        $number = $this->issueNumber($issue);
        $id = $this->boardId($issue, $number, (string) $request->input('brief', ''));
        $brief = trim((string) $request->input('brief', ''));

        $item = $board->upsert($id, [
            'state' => 'queued',
            'lifecycle' => 'queued',
            'owner' => $chair,
            'issue' => $number,
            'hops' => 0,
            'note' => $brief !== '' ? $brief : 'assigned '.$issue,
        ]);

        $this->hallwayAck($mattermost, $id, $issue, $chair);

        return response()->json([
            'ok' => true,
            'board_id' => $id,
            'item' => $item,
        ]);
    }

    private function issueNumber(string $issue): int
    {
        if (preg_match('/#(\d+)/', $issue, $m) === 1) {
            return (int) $m[1];
        }
        if (preg_match('/\/issues\/(\d+)/', $issue, $m) === 1) {
            return (int) $m[1];
        }

        return (int) $issue;
    }

    private function boardId(string $issue, int $number, string $brief): string
    {
        $hay = strtolower($issue.' '.$brief);
        foreach (['rider', 'hero-ebike', 'ranch-7620'] as $catalog) {
            if (str_contains($hay, $catalog)) {
                return $catalog;
            }
        }

        return $number > 0 ? 'issue-'.$number : 'assign';
    }

    private function hallwayAck(Client $mattermost, string $id, string $issue, string $chair): void
    {
        $hallway = (string) config('kit.mattermost.hallway_id');
        $dm = (string) config('kit.mattermost.dm_id');
        if ($hallway === '' || $hallway === $dm) {
            return;
        }

        $short = $number = $this->issueNumber($issue);
        $ref = $short > 0 ? '#'.$short : $issue;
        $mattermost->post($hallway, 'queued '.$id.' ← '.$ref.' ('.$chair.')');
    }
}
