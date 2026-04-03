<?php

namespace Tests\Feature;

use App\Models\Abonne;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecurityAndImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_protected_management_route(): void
    {
        $response = $this->get('/abonnes');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_import_abonnes_from_csv(): void
    {
        $user = User::factory()->create();

        $csv = <<<CSV
nom;prenom;telephone;email;sexe
El Idrissi;Sara;0611223344;sara@example.com;F
CSV;

        $file = UploadedFile::fake()->createWithContent('abonnes.csv', $csv);

        $response = $this->actingAs($user)->post(route('import_export.import.abonnes'), [
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.updated', 0);

        $this->assertDatabaseHas('abonnes', [
            'nom' => 'El Idrissi',
            'prenom' => 'Sara',
            'email' => 'sara@example.com',
            'sexe' => 'Femme',
        ]);
    }

    public function test_reclamation_requires_an_assurance_subscription(): void
    {
        $user = User::factory()->create();
        $abonne = Abonne::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '0600000000',
        ]);

        $service = Service::create([
            'nom' => 'Musculation',
            'type' => 'activite',
            'prix_mensuel' => 100,
            'prix_trimestriel' => 250,
            'prix_annuel' => 900,
            'statut' => 'actif',
        ]);

        $subscription = Subscription::create([
            'abonne_id' => $abonne->id,
            'service_id' => $service->id,
            'type_abonnement' => 'mensuel',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant' => 100,
            'remise' => 0,
            'montant_total' => 100,
            'montant_paye' => 0,
            'reste' => 100,
            'statut' => 'actif',
        ]);

        $response = $this->actingAs($user)->post(route('reclamation_assurances.store'), [
            'abonne_assurance_id' => $subscription->id,
            'type' => 'consultation',
            'montant_total' => 120,
            'date_reclamation' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['abonne_assurance_id']);
    }
}
