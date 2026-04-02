<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\Paiement;
use App\Models\Pointage;
use App\Models\ReclamationAssurance;
use App\Models\Service;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', $this->buildDashboardPayload());
    }

    public function dashboard()
    {
        return $this->index();
    }

    public function statistiques()
    {
        $payload = $this->buildDashboardPayload();

        return response()->json([
            'success' => true,
            'stats' => $payload['stats'],
            'revenus_mensuels' => $payload['revenus_mensuels'],
            'repartition_activites' => $payload['repartition_activites'],
        ]);
    }

    public function rapports()
    {
        return $this->renderReportPage('general');
    }

    public function rapportFinancier()
    {
        return $this->renderReportPage('financier');
    }

    public function rapportFrequentation()
    {
        return $this->renderReportPage('frequentation');
    }

    public function rapportAssurances()
    {
        return $this->renderReportPage('assurances');
    }

    public function rapportSubscriptions()
    {
        return $this->renderReportPage('subscriptions');
    }

    protected function renderReportPage(string $type)
    {
        return view('reports.index', [
            'reportType' => $type,
            'reportCards' => $this->buildReportCards(),
            'recentPayments' => Paiement::with(['subscription.abonne', 'subscription.service'])
                ->latest('date_paiement')
                ->take(8)
                ->get(),
            'expiringSubscriptions' => Subscription::with(['abonne', 'service'])
                ->where('statut', 'actif')
                ->whereBetween('date_fin', [today(), today()->addDays(15)])
                ->orderBy('date_fin')
                ->take(8)
                ->get(),
            'recentClaims' => class_exists(ReclamationAssurance::class)
                ? ReclamationAssurance::with(['abonne', 'company'])->latest('date_reclamation')->take(8)->get()
                : collect(),
        ]);
    }

    protected function buildDashboardPayload(): array
    {
        $stats = [
            'total_membres' => Abonne::count(),
            'subscriptions_actifs' => Subscription::where('statut', 'actif')
                ->where('date_fin', '>=', today())
                ->count(),
            'entrees_aujourdhui' => Pointage::whereDate('date_pointage', today())->count(),
            'revenu_mois' => $this->getRevenueMois(),
            'membres_nouveaux_mois' => Abonne::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'taux_renouvellement' => $this->calculateRenewalRate(),
        ];

        $revenus_mensuels = $this->getRevenusMensuels();
        $repartition_activites = $this->getRepartitionActivites();

        $derniers_membres = Abonne::with('subscriptionActif')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $subscriptions_expirant = Subscription::with(['abonne', 'activite'])
            ->where('statut', 'actif')
            ->whereBetween('date_fin', [today(), today()->addDays(7)])
            ->orderBy('date_fin')
            ->get();

        $dernieres_entrees = Pointage::with(['abonne' => function ($query) {
            $query->with('subscriptionActif.service');
        }])
            ->whereDate('date_pointage', today())
            ->orderBy('date_pointage', 'desc')
            ->take(10)
            ->get();

        return compact(
            'stats',
            'revenus_mensuels',
            'repartition_activites',
            'derniers_membres',
            'subscriptions_expirant',
            'dernieres_entrees'
        );
    }

    protected function buildReportCards(): array
    {
        $today = today();

        return [
            'general' => [
                ['label' => 'Membres', 'value' => Abonne::count()],
                ['label' => 'Subscriptions actives', 'value' => Subscription::where('statut', 'actif')->count()],
                ['label' => 'Paiements ce mois', 'value' => number_format((float) $this->getRevenueMois(), 2) . ' DH'],
                ['label' => 'Entrees aujourd hui', 'value' => Pointage::whereDate('date_pointage', $today)->count()],
            ],
            'financier' => [
                ['label' => 'Aujourd hui', 'value' => number_format((float) Paiement::today()->sum('montant'), 2) . ' DH'],
                ['label' => 'Ce mois', 'value' => number_format((float) Paiement::thisMonth()->sum('montant'), 2) . ' DH'],
                ['label' => 'Cette annee', 'value' => number_format((float) Paiement::whereYear('date_paiement', now()->year)->sum('montant'), 2) . ' DH'],
                ['label' => 'Paiements', 'value' => Paiement::count()],
            ],
            'frequentation' => [
                ['label' => 'Entrees aujourd hui', 'value' => Pointage::whereDate('date_pointage', $today)->count()],
                ['label' => 'Cette semaine', 'value' => Pointage::whereBetween('date_pointage', [now()->startOfWeek(), now()->endOfWeek()])->count()],
                ['label' => 'Ce mois', 'value' => Pointage::whereMonth('date_pointage', now()->month)->whereYear('date_pointage', now()->year)->count()],
                ['label' => 'Membres actifs', 'value' => Subscription::where('statut', 'actif')->count()],
            ],
            'assurances' => [
                ['label' => 'Services assurance', 'value' => Service::where('type', 'assurance')->count()],
                ['label' => 'Subscriptions assurance', 'value' => Subscription::whereHas('service', fn ($q) => $q->where('type', 'assurance'))->count()],
                ['label' => 'Reclamations', 'value' => class_exists(ReclamationAssurance::class) ? ReclamationAssurance::count() : 0],
                ['label' => 'En attente', 'value' => class_exists(ReclamationAssurance::class) ? ReclamationAssurance::where('statut', 'en_attente')->count() : 0],
            ],
            'subscriptions' => [
                ['label' => 'Total subscriptions', 'value' => Subscription::count()],
                ['label' => 'Actives', 'value' => Subscription::where('statut', 'actif')->count()],
                ['label' => 'Expirent en 7j', 'value' => Subscription::where('statut', 'actif')->whereBetween('date_fin', [$today, $today->copy()->addDays(7)])->count()],
                ['label' => 'Taux renouvellement', 'value' => $this->calculateRenewalRate() . '%'],
            ],
        ];
    }

    private function getRevenueMois()
    {
        return Paiement::whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');
    }

    private function calculateRenewalRate()
    {
        $totalSubscriptions = Subscription::count();
        $subscriptionsRenouveles = Subscription::whereHas('abonne', function ($query) {
            $query->whereHas('subscriptions', function ($subQuery) {
                $subQuery->where('id', '!=', DB::raw('subscriptions.id'));
            });
        })->count();

        return $totalSubscriptions > 0 ? round(($subscriptionsRenouveles / $totalSubscriptions) * 100, 2) : 0;
    }

    private function getRevenusMensuels()
    {
        $revenus = [];
        $mois = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois[] = $date->translatedFormat('M');

            $revenu = Paiement::whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->sum('montant');

            $revenus[] = $revenu ?? 0;
        }

        return [
            'labels' => $mois,
            'data' => $revenus,
        ];
    }

    private function getRepartitionActivites()
    {
        $activites = Service::where('type', 'activite')
            ->withCount(['subscriptions' => function ($query) {
                $query->where('statut', 'actif')
                    ->where('date_fin', '>=', today());
            }])
            ->where('statut', 'actif')
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
        ];

        foreach ($activites as $index => $activite) {
            $labels[] = $activite->nom;
            $data[] = $activite->subscriptions_count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($labels)),
        ];
    }
}
