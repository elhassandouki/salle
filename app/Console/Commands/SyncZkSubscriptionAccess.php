<?php

namespace App\Console\Commands;

use App\Services\ZkSubscriptionAccessService;
use Illuminate\Console\Command;

class SyncZkSubscriptionAccess extends Command
{
    protected $signature = 'subscriptions:sync-zk-access';

    protected $description = 'Expire les abonnements termines et synchronise l acces ZKTeco.';

    public function handle(ZkSubscriptionAccessService $accessService): int
    {
        $result = $accessService->expireAndSyncActivitySubscriptions();

        $this->info(sprintf(
            'Termines maj: %d | abonnes synchronises: %d',
            $result['expired_count'] ?? 0,
            $result['synced_abonnes'] ?? 0
        ));

        return self::SUCCESS;
    }
}
