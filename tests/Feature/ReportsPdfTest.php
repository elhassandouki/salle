<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_accepts_date_range_filters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('rapports.financier', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $response->assertOk()
            ->assertSee('Rapport financier')
            ->assertSee('2026-01-01', false)
            ->assertSee('2026-01-31', false);
    }

    public function test_report_pdf_can_be_downloaded_for_a_date_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('rapports.pdf', [
            'type' => 'subscriptions',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_etats_page_accepts_subscription_filters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('rapports.etats', [
            'etat_type' => 'subscriptions',
            'date_field' => 'date_debut',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'statut' => 'actif',
            'type_abonnement' => 'mensuel',
        ]));

        $response->assertOk()
            ->assertSee('Etat des subscriptions')
            ->assertSee('2026-01-01', false)
            ->assertSee('2026-01-31', false);
    }

    public function test_etats_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('rapports.etats.pdf', [
            'etat_type' => 'paiements',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
            'mode_paiement' => 'especes',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
