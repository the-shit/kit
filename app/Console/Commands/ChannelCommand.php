<?php

namespace App\Console\Commands;

use App\Mattermost\Client;
use Illuminate\Console\Command;

class ChannelCommand extends Command
{
    protected $signature = 'kit:channel
        {name : Channel slug}
        {--display= : Display name}
        {--purpose= : Purpose}
        {--add= : Comma @users or ids}';

    protected $description = 'Find or create a Mattermost open channel';

    public function handle(Client $mm): int
    {
        $out = $mm->createChannel(
            (string) $this->argument('name'),
            (string) $this->option('display'),
            (string) $this->option('purpose'),
        );
        if (isset($out['error'])) {
            $this->error($out['error']);

            return self::FAILURE;
        }

        $add = trim((string) $this->option('add'));
        if ($add !== '') {
            foreach (preg_split('/\s*,\s*/', $add) ?: [] as $who) {
                $uid = $mm->resolveUser($who);
                if ($uid !== null) {
                    $mm->addMember($out['id'], $uid);
                }
            }
        }

        $this->line((($out['created'] ?? false) ? 'created' : 'exists').' '.$out['id']);

        return self::SUCCESS;
    }
}
