<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Services\ZkSubscriptionAccessService;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        app(ZkSubscriptionAccessService::class)->syncAbonneAccess((int) $subscription->abonne_id);
    }

    public function updated(Subscription $subscription): void
    {
        $service = app(ZkSubscriptionAccessService::class);

        $service->syncAbonneAccess((int) $subscription->abonne_id);

        $originalAbonneId = (int) $subscription->getOriginal('abonne_id');

        if ($originalAbonneId && $originalAbonneId !== (int) $subscription->abonne_id) {
            $service->syncAbonneAccess($originalAbonneId);
        }
    }

    public function deleted(Subscription $subscription): void
    {
        app(ZkSubscriptionAccessService::class)->syncAbonneAccess((int) $subscription->abonne_id);
    }
}
