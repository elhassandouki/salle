<?php

namespace Tests\Feature;

use App\Models\Abonne;
use App\Models\Service;
use App\Models\Subscription;
use App\Services\ZKTecoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ZkSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_active_activity_subscription_is_added_to_zkteco(): void
    {
        config(['zkteco.sync.auto' => true]);

        $mock = Mockery::mock(ZKTecoService::class);
        $mock->shouldReceive('setUser')
            ->once()
            ->with('1', 'Doe John', 'CARD-001')
            ->andReturn(true);
        $mock->shouldReceive('deleteUser')->never();
        $this->app->instance(ZKTecoService::class, $mock);

        $abonne = Abonne::create([
            'nom' => 'Doe',
            'prenom' => 'John',
            'telephone' => '0600000000',
            'card_id' => 'CARD-001',
        ]);

        $service = Service::create([
            'nom' => 'Musculation',
            'type' => 'activite',
            'prix_mensuel' => 100,
            'prix_trimestriel' => 250,
            'prix_annuel' => 900,
            'statut' => 'actif',
        ]);

        Subscription::create([
            'abonne_id' => $abonne->id,
            'service_id' => $service->id,
            'type_abonnement' => 'mensuel',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant' => 100,
            'remise' => 0,
            'montant_total' => 100,
            'montant_paye' => 100,
            'reste' => 0,
            'statut' => 'actif',
        ]);

        $this->assertDatabaseHas('abonnes', [
            'id' => $abonne->id,
            'uid' => (string) $abonne->id,
        ]);
    }

    public function test_unpaid_activity_subscription_is_not_added_to_zkteco(): void
    {
        config(['zkteco.sync.auto' => true]);

        $mock = Mockery::mock(ZKTecoService::class);
        $mock->shouldReceive('setUser')->never();
        $mock->shouldReceive('deleteUser')
            ->once()
            ->with('1')
            ->andReturn(true);
        $this->app->instance(ZKTecoService::class, $mock);

        $abonne = Abonne::create([
            'nom' => 'Late',
            'prenom' => 'Payment',
            'telephone' => '0600000010',
        ]);

        $service = Service::create([
            'nom' => 'Fitness',
            'type' => 'activite',
            'prix_mensuel' => 150,
            'prix_trimestriel' => 400,
            'prix_annuel' => 1200,
            'statut' => 'actif',
        ]);

        Subscription::create([
            'abonne_id' => $abonne->id,
            'service_id' => $service->id,
            'type_abonnement' => 'mensuel',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant' => 150,
            'remise' => 0,
            'montant_total' => 150,
            'montant_paye' => 50,
            'reste' => 100,
            'statut' => 'actif',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'abonne_id' => $abonne->id,
            'reste' => 100,
            'statut' => 'actif',
        ]);
    }

    public function test_expired_activity_subscription_is_removed_from_zkteco_by_command(): void
    {
        config(['zkteco.sync.auto' => true]);

        $mock = Mockery::mock(ZKTecoService::class);
        $mock->shouldReceive('deleteUser')
            ->once()
            ->with('ABN-7')
            ->andReturn(true);
        $mock->shouldReceive('setUser')->never();
        $this->app->instance(ZKTecoService::class, $mock);

        $abonne = Abonne::create([
            'uid' => 'ABN-7',
            'nom' => 'Expired',
            'prenom' => 'User',
            'telephone' => '0600000001',
        ]);

        $service = Service::create([
            'nom' => 'Cardio',
            'type' => 'activite',
            'prix_mensuel' => 120,
            'prix_trimestriel' => 300,
            'prix_annuel' => 1000,
            'statut' => 'actif',
        ]);

        Subscription::withoutEvents(function () use ($abonne, $service) {
            Subscription::create([
                'abonne_id' => $abonne->id,
                'service_id' => $service->id,
                'type_abonnement' => 'mensuel',
                'date_debut' => now()->subMonths(2)->toDateString(),
                'date_fin' => now()->subDay()->toDateString(),
                'montant' => 120,
                'remise' => 0,
                'montant_total' => 120,
                'montant_paye' => 120,
                'reste' => 0,
                'statut' => 'actif',
            ]);
        });

        $this->artisan('subscriptions:sync-zk-access')->assertSuccessful();

        $this->assertDatabaseHas('subscriptions', [
            'abonne_id' => $abonne->id,
            'statut' => 'expire',
        ]);
    }
}
