<?php

namespace App\Services;

use App\Models\Abonne;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class ZkSubscriptionAccessService
{
    public function __construct(private ZKTecoService $zkService)
    {
    }

    public function syncSubscriptionAccess(Subscription|int $subscription): array
    {
        $subscriptionModel = $subscription instanceof Subscription
            ? $subscription
            : Subscription::find($subscription);

        if (! $subscriptionModel) {
            return [
                'success' => false,
                'action' => 'skipped',
                'message' => 'Subscription introuvable.',
            ];
        }

        return $this->syncAbonneAccess((int) $subscriptionModel->abonne_id);
    }

    public function syncAbonneAccess(Abonne|int $abonne): array
    {
        if (! config('zkteco.sync.auto', true)) {
            return [
                'success' => true,
                'action' => 'skipped',
                'message' => 'Synchronisation ZKTeco automatique desactivee.',
            ];
        }

        $abonneModel = $abonne instanceof Abonne
            ? $abonne->loadMissing('subscriptions.service')
            : Abonne::with('subscriptions.service')->find($abonne);

        if (! $abonneModel) {
            return [
                'success' => false,
                'action' => 'skipped',
                'message' => 'Abonne introuvable.',
            ];
        }

        if ($this->shouldHaveDeviceAccess($abonneModel)) {
            return $this->grantAccess($abonneModel);
        }

        return $this->revokeAccess($abonneModel);
    }

    public function expireAndSyncActivitySubscriptions(): array
    {
        $subscriptions = Subscription::with(['abonne', 'service'])
            ->whereHas('service', function ($query) {
                $query->where('type', 'activite');
            })
            ->get();

        $expiredCount = 0;
        $abonneIdsToSync = [];

        foreach ($subscriptions as $subscription) {
            $isExpiredByDate = $subscription->date_fin && $subscription->date_fin->isPast();
            $normalizedStatus = $this->normalizeStatus($subscription->statut);

            if ($isExpiredByDate && $normalizedStatus !== 'expire' && $normalizedStatus !== 'suspendu') {
                $subscription->forceFill(['statut' => 'expire'])->saveQuietly();
                $expiredCount++;
            }

            if ($normalizedStatus === 'actif' || $isExpiredByDate || $normalizedStatus === 'suspendu') {
                $abonneIdsToSync[] = (int) $subscription->abonne_id;
            }
        }

        $results = [];

        foreach (array_values(array_unique($abonneIdsToSync)) as $abonneId) {
            $results[] = $this->syncAbonneAccess($abonneId);
        }

        return [
            'success' => true,
            'expired_count' => $expiredCount,
            'synced_abonnes' => count($results),
            'results' => $results,
        ];
    }

    private function shouldHaveDeviceAccess(Abonne $abonne): bool
    {
        return $abonne->subscriptions->contains(function (Subscription $subscription) {
            return $subscription->service?->type === 'activite'
                && $this->normalizeStatus($subscription->statut) === 'actif'
                && $subscription->date_fin
                && ! $subscription->date_fin->isPast()
                && $this->isFullyPaid($subscription);
        });
    }

    private function grantAccess(Abonne $abonne): array
    {
        $uid = $this->ensureUid($abonne);
        $success = (bool) $this->zkService->setUser(
            $uid,
            trim($abonne->nom . ' ' . $abonne->prenom),
            $abonne->card_id ?: ''
        );

        if (! $success) {
            Log::warning('Echec ajout utilisateur ZKTeco.', ['abonne_id' => $abonne->id, 'uid' => $uid]);
        }

        return [
            'success' => $success,
            'action' => 'grant',
            'uid' => $uid,
        ];
    }

    private function revokeAccess(Abonne $abonne): array
    {
        $uid = $abonne->uid ?: (string) $abonne->id;
        $success = $this->zkService->deleteUser($uid);

        if (! $success) {
            Log::warning('Echec suppression utilisateur ZKTeco.', ['abonne_id' => $abonne->id, 'uid' => $uid]);
        }

        return [
            'success' => $success,
            'action' => 'revoke',
            'uid' => $uid,
        ];
    }

    private function ensureUid(Abonne $abonne): string
    {
        if ($abonne->uid) {
            return $abonne->uid;
        }

        $uid = (string) $abonne->id;
        $abonne->forceFill(['uid' => $uid])->saveQuietly();

        return $uid;
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©', 'expirط·آ·ط¢آ£ط·آ¢ط¢آ©' => 'expire',
            default => (string) $status,
        };
    }

    private function isFullyPaid(Subscription $subscription): bool
    {
        $reste = (float) ($subscription->reste ?? 0);
        $montantTotal = (float) ($subscription->montant_total ?? $subscription->montant ?? 0);
        $montantPaye = (float) ($subscription->montant_paye ?? 0);

        if ($montantTotal <= 0) {
            return true;
        }

        return $reste <= 0.0001 || $montantPaye >= $montantTotal;
    }
}
