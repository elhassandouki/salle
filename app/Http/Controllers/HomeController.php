<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
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

    public function rapports(Request $request)
    {
        return $this->renderReportPage($request, 'general');
    }

    public function rapportFinancier(Request $request)
    {
        return $this->renderReportPage($request, 'financier');
    }

    public function rapportFrequentation(Request $request)
    {
        return $this->renderReportPage($request, 'frequentation');
    }

    public function rapportAssurances(Request $request)
    {
        return $this->renderReportPage($request, 'assurances');
    }

    public function rapportSubscriptions(Request $request)
    {
        return $this->renderReportPage($request, 'subscriptions');
    }

    public function etats(Request $request)
    {
        return view('reports.etats', $this->buildEtatPageData($request));
    }

    public function exportEtatPdf(Request $request)
    {
        $data = $this->buildEtatPageData($request);

        return Pdf::loadView('reports.pdf', [
            'reportTitle' => $data['stateTitle'],
            'reportCards' => $data['cards'],
            'reportColumns' => $data['columns'],
            'reportRows' => $data['rows'],
            'reportDescription' => $data['description'],
            'reportMeta' => $data['reportMeta'],
            'dateRangeLabel' => $data['dateRangeLabel'],
        ])->setPaper('a4', 'portrait')
            ->download('etat_' . $data['stateType'] . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportPdf(Request $request, string $type)
    {
        $data = $this->buildReportPageData($request, $type);

        return Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('rapport_' . $type . '_' . now()->format('Ymd_His') . '.pdf');
    }

    protected function renderReportPage(Request $request, string $type)
    {
        return view('reports.index', $this->buildReportPageData($request, $type));
    }

    protected function buildReportPageData(Request $request, string $type): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $details = $this->buildReportDetails($type, $dateFrom, $dateTo);

        return [
            'reportType' => $type,
            'reportTitle' => $this->reportTitle($type),
            'reportCards' => $this->buildFilteredReportCards($type, $dateFrom, $dateTo),
            'reportRows' => $details['rows'],
            'reportColumns' => $details['columns'],
            'reportDescription' => $details['description'],
            'reportMeta' => [],
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'dateRangeLabel' => $dateFrom->format('d/m/Y') . ' - ' . $dateTo->format('d/m/Y'),
        ];
    }

    protected function buildEtatPageData(Request $request): array
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $stateType = $request->input('etat_type', 'subscriptions');
        $serviceId = $request->input('service_id');
        $subscriptionStatus = $request->input('statut');
        $paymentMode = $request->input('mode_paiement');
        $subscriptionType = $request->input('type_abonnement');
        $dateField = $request->input('date_field', $stateType === 'paiements' ? 'date_paiement' : 'date_debut');

        if (! in_array($stateType, ['subscriptions', 'paiements'], true)) {
            $stateType = 'subscriptions';
        }

        $payload = $stateType === 'paiements'
            ? $this->buildPaymentsStateData($dateFrom, $dateTo, $serviceId, $paymentMode)
            : $this->buildSubscriptionsStateData($dateFrom, $dateTo, $serviceId, $subscriptionStatus, $subscriptionType, $dateField);

        return [
            'stateType' => $stateType,
            'stateTitle' => $stateType === 'paiements' ? 'Etat des paiements' : 'Etat des subscriptions',
            'cards' => $payload['cards'],
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'description' => $payload['description'],
            'reportMeta' => array_values(array_filter([
                ['label' => 'Type', 'value' => $stateType === 'paiements' ? 'Paiements' : 'Subscriptions'],
                ['label' => 'Service', 'value' => Service::whereKey($serviceId)->value('nom')],
                ['label' => 'Statut', 'value' => $subscriptionStatus ? ucfirst($subscriptionStatus) : null],
                ['label' => 'Mode paiement', 'value' => $paymentMode ? ucfirst($paymentMode) : null],
                ['label' => 'Type abonnement', 'value' => $subscriptionType ? ucfirst($subscriptionType) : null],
                ['label' => 'Filtre date', 'value' => match ($dateField) {
                    'date_fin' => 'Date fin',
                    'date_paiement' => 'Date paiement',
                    default => 'Date debut',
                }],
            ], fn ($item) => filled($item['value'] ?? null))),
            'services' => Service::orderBy('nom')->get(['id', 'nom', 'type']),
            'filters' => [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'service_id' => $serviceId,
                'statut' => $subscriptionStatus,
                'mode_paiement' => $paymentMode,
                'type_abonnement' => $subscriptionType,
                'date_field' => $dateField,
            ],
            'dateRangeLabel' => $dateFrom->format('d/m/Y') . ' - ' . $dateTo->format('d/m/Y'),
        ];
    }

    protected function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    protected function buildFilteredReportCards(string $type, Carbon $dateFrom, Carbon $dateTo): array
    {
        return match ($type) {
            'financier' => [
                ['label' => 'Paiements', 'value' => Paiement::whereBetween('date_paiement', [$dateFrom, $dateTo])->count()],
                ['label' => 'Montant total', 'value' => number_format((float) Paiement::whereBetween('date_paiement', [$dateFrom, $dateTo])->sum('montant'), 2) . ' DH'],
                ['label' => 'Panier moyen', 'value' => number_format((float) Paiement::whereBetween('date_paiement', [$dateFrom, $dateTo])->avg('montant'), 2) . ' DH'],
                ['label' => 'Modes utilises', 'value' => Paiement::whereBetween('date_paiement', [$dateFrom, $dateTo])->distinct('mode_paiement')->count('mode_paiement')],
            ],
            'frequentation' => [
                ['label' => 'Pointages', 'value' => Pointage::whereBetween('date_pointage', [$dateFrom, $dateTo])->count()],
                ['label' => 'Entrees', 'value' => Pointage::whereBetween('date_pointage', [$dateFrom, $dateTo])->where('type', 'entree')->count()],
                ['label' => 'Sorties', 'value' => Pointage::whereBetween('date_pointage', [$dateFrom, $dateTo])->where('type', 'sortie')->count()],
                ['label' => 'Non synchronises', 'value' => Pointage::whereBetween('date_pointage', [$dateFrom, $dateTo])->where('synced', false)->count()],
            ],
            'assurances' => [
                ['label' => 'Reclamations', 'value' => ReclamationAssurance::whereBetween('date_reclamation', [$dateFrom->toDateString(), $dateTo->toDateString()])->count()],
                ['label' => 'Montant demande', 'value' => number_format((float) ReclamationAssurance::whereBetween('date_reclamation', [$dateFrom->toDateString(), $dateTo->toDateString()])->sum('montant_total'), 2) . ' DH'],
                ['label' => 'Montant rembourse', 'value' => number_format((float) ReclamationAssurance::whereBetween('date_reclamation', [$dateFrom->toDateString(), $dateTo->toDateString()])->sum('montant_rembourse'), 2) . ' DH'],
                ['label' => 'En attente', 'value' => ReclamationAssurance::whereBetween('date_reclamation', [$dateFrom->toDateString(), $dateTo->toDateString()])->where('statut', 'en_attente')->count()],
            ],
            'subscriptions' => [
                ['label' => 'Abonnements', 'value' => Subscription::whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])->count()],
                ['label' => 'Actifs', 'value' => Subscription::whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])->where('statut', 'actif')->count()],
                ['label' => 'Montant total', 'value' => number_format((float) Subscription::whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])->sum('montant_total'), 2) . ' DH'],
                ['label' => 'Reste total', 'value' => number_format((float) Subscription::whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])->sum('reste'), 2) . ' DH'],
            ],
            default => [
                ['label' => 'Nouveaux membres', 'value' => Abonne::whereBetween('created_at', [$dateFrom, $dateTo])->count()],
                ['label' => 'Paiements', 'value' => Paiement::whereBetween('date_paiement', [$dateFrom, $dateTo])->count()],
                ['label' => 'Pointages', 'value' => Pointage::whereBetween('date_pointage', [$dateFrom, $dateTo])->count()],
                ['label' => 'Abonnements crees', 'value' => Subscription::whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])->count()],
            ],
        };
    }

    protected function buildReportDetails(string $type, Carbon $dateFrom, Carbon $dateTo): array
    {
        return match ($type) {
            'financier' => $this->buildFinancierReportDetails($dateFrom, $dateTo),
            'frequentation' => $this->buildFrequentationReportDetails($dateFrom, $dateTo),
            'assurances' => $this->buildAssurancesReportDetails($dateFrom, $dateTo),
            'subscriptions' => $this->buildSubscriptionsReportDetails($dateFrom, $dateTo),
            default => $this->buildGeneralReportDetails($dateFrom, $dateTo),
        };
    }

    protected function buildGeneralReportDetails(Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = Abonne::with('subscriptionActif.service')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->get()
            ->map(fn ($abonne) => [
                'Membre' => $abonne->prenom . ' ' . $abonne->nom,
                'CIN' => $abonne->cin ?: '-',
                'Telephone' => $abonne->telephone ?: '-',
                'Abonnement actif' => $abonne->subscriptionActif?->service?->nom ?: 'Aucun',
                'Date inscription' => optional($abonne->created_at)->format('d/m/Y H:i'),
            ]);

        return [
            'description' => 'Liste detaillee des membres inscrits pendant la periode selectionnee.',
            'columns' => ['Membre', 'CIN', 'Telephone', 'Abonnement actif', 'Date inscription'],
            'rows' => $rows,
        ];
    }

    protected function buildFinancierReportDetails(Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = Paiement::with(['subscription.abonne', 'subscription.service'])
            ->whereBetween('date_paiement', [$dateFrom, $dateTo])
            ->latest('date_paiement')
            ->get()
            ->map(fn ($paiement) => [
                'Date' => optional($paiement->date_paiement)->format('d/m/Y H:i'),
                'Membre' => optional($paiement->subscription?->abonne)->prenom . ' ' . optional($paiement->subscription?->abonne)->nom,
                'Service' => $paiement->subscription?->service?->nom ?: '-',
                'Montant' => number_format((float) $paiement->montant, 2) . ' DH',
                'Mode' => $paiement->mode_paiement_text,
                'Reference' => $paiement->reference ?: '-',
                'Notes' => $paiement->notes ?: '-',
            ]);

        return [
            'description' => 'Tous les paiements enregistres entre les deux dates choisies.',
            'columns' => ['Date', 'Membre', 'Service', 'Montant', 'Mode', 'Reference', 'Notes'],
            'rows' => $rows,
        ];
    }

    protected function buildFrequentationReportDetails(Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = Pointage::with('abonne')
            ->whereBetween('date_pointage', [$dateFrom, $dateTo])
            ->latest('date_pointage')
            ->get()
            ->map(fn ($pointage) => [
                'Date' => optional($pointage->date_pointage)->format('d/m/Y H:i'),
                'Membre' => optional($pointage->abonne)->prenom . ' ' . optional($pointage->abonne)->nom,
                'CIN' => $pointage->abonne?->cin ?: '-',
                'Type' => $pointage->type_text,
                'UID' => $pointage->uid ?: '-',
                'Sync' => $pointage->synced ? 'Oui' : 'Non',
            ]);

        return [
            'description' => 'Historique detaille des entrees et sorties sur la periode.',
            'columns' => ['Date', 'Membre', 'CIN', 'Type', 'UID', 'Sync'],
            'rows' => $rows,
        ];
    }

    protected function buildAssurancesReportDetails(Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = ReclamationAssurance::with(['abonne', 'company'])
            ->whereBetween('date_reclamation', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->latest('date_reclamation')
            ->get()
            ->map(fn ($claim) => [
                'Date reclamation' => optional($claim->date_reclamation)->format('d/m/Y'),
                'Membre' => optional($claim->abonne)->prenom . ' ' . optional($claim->abonne)->nom,
                'Compagnie' => $claim->company?->nom ?: '-',
                'Type' => $claim->type_text,
                'Montant total' => number_format((float) $claim->montant_total, 2) . ' DH',
                'Montant rembourse' => number_format((float) $claim->montant_rembourse, 2) . ' DH',
                'Statut' => $claim->statut_text,
                'Date traitement' => optional($claim->date_traitement)->format('d/m/Y') ?: '-',
            ]);

        return [
            'description' => 'Suivi detaille des reclamations assurances pendant la periode.',
            'columns' => ['Date reclamation', 'Membre', 'Compagnie', 'Type', 'Montant total', 'Montant rembourse', 'Statut', 'Date traitement'],
            'rows' => $rows,
        ];
    }

    protected function buildSubscriptionsReportDetails(Carbon $dateFrom, Carbon $dateTo): array
    {
        $rows = Subscription::with(['abonne', 'service'])
            ->whereBetween('date_debut', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->latest('date_debut')
            ->get()
            ->map(fn ($subscription) => [
                'Membre' => optional($subscription->abonne)->prenom . ' ' . optional($subscription->abonne)->nom,
                'Service' => $subscription->service?->nom ?: '-',
                'Type' => ucfirst((string) $subscription->type_abonnement),
                'Date debut' => optional($subscription->date_debut)->format('d/m/Y'),
                'Date fin' => optional($subscription->date_fin)->format('d/m/Y'),
                'Montant total' => number_format((float) $subscription->montant_total, 2) . ' DH',
                'Paye' => number_format((float) $subscription->montant_paye, 2) . ' DH',
                'Reste' => number_format((float) $subscription->reste, 2) . ' DH',
                'Statut' => $subscription->statut,
            ]);

        return [
            'description' => 'Etat detaille des abonnements crees pendant la periode selectionnee.',
            'columns' => ['Membre', 'Service', 'Type', 'Date debut', 'Date fin', 'Montant total', 'Paye', 'Reste', 'Statut'],
            'rows' => $rows,
        ];
    }

    protected function reportTitle(string $type): string
    {
        return match ($type) {
            'financier' => 'Rapport financier',
            'frequentation' => 'Rapport de frequentation',
            'assurances' => 'Rapport assurances',
            'subscriptions' => 'Rapport subscriptions',
            default => 'Rapport general',
        };
    }

    protected function buildDashboardPayload(): array
    {
        $today = today();

        $stats = [
            'total_membres' => Abonne::count(),
            'subscriptions_actifs' => Subscription::where('statut', 'actif')
                ->where('date_fin', '>=', $today)
                ->count(),
            'entrees_aujourdhui' => Pointage::whereDate('date_pointage', $today)->count(),
            'revenu_mois' => $this->getRevenueMois(),
            'membres_nouveaux_mois' => Abonne::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'taux_renouvellement' => $this->calculateRenewalRate(),
            'activites_actives' => Service::where('type', 'activite')->where('statut', 'actif')->count(),
            'paiements_aujourdhui' => Paiement::whereDate('date_paiement', $today)->sum('montant'),
            'pointages_non_sync' => Pointage::where('synced', false)->count(),
        ];

        $revenus_mensuels = $this->getRevenusMensuels();
        $repartition_activites = $this->getRepartitionActivites();
        $revenus_resume = [
            'moyenne' => count($revenus_mensuels['data']) > 0
                ? round(array_sum($revenus_mensuels['data']) / count($revenus_mensuels['data']), 2)
                : 0,
            'maximum' => count($revenus_mensuels['data']) > 0 ? max($revenus_mensuels['data']) : 0,
            'minimum' => count($revenus_mensuels['data']) > 0 ? min($revenus_mensuels['data']) : 0,
        ];
        $total_abonnes_activites = array_sum($repartition_activites['data']);

        $derniers_membres = Abonne::with('subscriptionActif')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $subscriptions_expirant = Subscription::with(['abonne', 'activite'])
            ->where('statut', 'actif')
            ->whereBetween('date_fin', [$today, $today->copy()->addDays(7)])
            ->orderBy('date_fin')
            ->get();

        $dernieres_entrees = Pointage::with(['abonne' => function ($query) {
            $query->with('subscriptionActif.service');
        }])
            ->whereDate('date_pointage', today())
            ->orderBy('date_pointage', 'desc')
            ->take(10)
            ->get();

        $paiements_recents = Paiement::with(['subscription.abonne', 'subscription.service'])
            ->latest('date_paiement')
            ->take(6)
            ->get();

        $system_status = [
            'base_donnees' => DB::connection()->getDatabaseName() ?: config('database.default'),
            'zk_pending' => $stats['pointages_non_sync'],
            'last_sync_at' => optional(
                Pointage::where('synced', true)->latest('updated_at')->first()
            )->updated_at?->format('d/m/Y H:i'),
        ];

        return compact(
            'stats',
            'revenus_mensuels',
            'revenus_resume',
            'repartition_activites',
            'derniers_membres',
            'subscriptions_expirant',
            'dernieres_entrees',
            'paiements_recents',
            'system_status'
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

    protected function buildSubscriptionsStateData(
        Carbon $dateFrom,
        Carbon $dateTo,
        ?string $serviceId,
        ?string $subscriptionStatus,
        ?string $subscriptionType,
        ?string $dateField
    ): array {
        $dateField = in_array($dateField, ['date_debut', 'date_fin'], true) ? $dateField : 'date_debut';

        $query = Subscription::with(['abonne', 'service', 'paiements'])
            ->whereBetween($dateField, [$dateFrom->toDateString(), $dateTo->toDateString()]);

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($subscriptionStatus) {
            $query->where('statut', $subscriptionStatus);
        }

        if ($subscriptionType) {
            $query->where('type_abonnement', $subscriptionType);
        }

        $subscriptions = $query->latest($dateField)->get();

        return [
            'description' => 'Etat filtre des subscriptions avec date, service, statut et montant restant.',
            'cards' => [
                ['label' => 'Subscriptions', 'value' => $subscriptions->count()],
                ['label' => 'Actifs', 'value' => $subscriptions->where('statut', 'actif')->count()],
                ['label' => 'Montant total', 'value' => number_format((float) $subscriptions->sum('montant_total'), 2) . ' DH'],
                ['label' => 'Reste a encaisser', 'value' => number_format((float) $subscriptions->sum('reste'), 2) . ' DH'],
            ],
            'columns' => ['Membre', 'Service', 'Type', 'Statut', 'Date debut', 'Date fin', 'Montant total', 'Paye', 'Reste', 'Paiements'],
            'rows' => $subscriptions->map(fn ($subscription) => [
                'Membre' => trim((optional($subscription->abonne)->prenom ?: '') . ' ' . (optional($subscription->abonne)->nom ?: '')) ?: '-',
                'Service' => $subscription->service?->nom ?: '-',
                'Type' => ucfirst((string) $subscription->type_abonnement),
                'Statut' => ucfirst((string) $subscription->statut),
                'Date debut' => optional($subscription->date_debut)->format('d/m/Y') ?: '-',
                'Date fin' => optional($subscription->date_fin)->format('d/m/Y') ?: '-',
                'Montant total' => number_format((float) $subscription->montant_total, 2) . ' DH',
                'Paye' => number_format((float) $subscription->montant_paye, 2) . ' DH',
                'Reste' => number_format((float) $subscription->reste, 2) . ' DH',
                'Paiements' => $subscription->paiements->count(),
            ]),
        ];
    }

    protected function buildPaymentsStateData(
        Carbon $dateFrom,
        Carbon $dateTo,
        ?string $serviceId,
        ?string $paymentMode
    ): array {
        $query = Paiement::with(['subscription.abonne', 'subscription.service'])
            ->whereBetween('date_paiement', [$dateFrom, $dateTo]);

        if ($serviceId) {
            $query->whereHas('subscription', fn ($subscriptionQuery) => $subscriptionQuery->where('service_id', $serviceId));
        }

        if ($paymentMode) {
            $query->where('mode_paiement', $paymentMode);
        }

        $payments = $query->latest('date_paiement')->get();

        return [
            'description' => 'Etat filtre des paiements par date, service et mode de paiement.',
            'cards' => [
                ['label' => 'Paiements', 'value' => $payments->count()],
                ['label' => 'Montant total', 'value' => number_format((float) $payments->sum('montant'), 2) . ' DH'],
                ['label' => 'Panier moyen', 'value' => number_format((float) $payments->avg('montant'), 2) . ' DH'],
                ['label' => 'Services touches', 'value' => $payments->pluck('subscription.service_id')->filter()->unique()->count()],
            ],
            'columns' => ['Date paiement', 'Membre', 'Service', 'Montant', 'Mode', 'Reference', 'Notes'],
            'rows' => $payments->map(fn ($payment) => [
                'Date paiement' => optional($payment->date_paiement)->format('d/m/Y H:i') ?: '-',
                'Membre' => trim((optional(optional($payment->subscription)->abonne)->prenom ?: '') . ' ' . (optional(optional($payment->subscription)->abonne)->nom ?: '')) ?: '-',
                'Service' => optional($payment->subscription)->service?->nom ?: '-',
                'Montant' => number_format((float) $payment->montant, 2) . ' DH',
                'Mode' => $payment->mode_paiement_text,
                'Reference' => $payment->reference ?: '-',
                'Notes' => $payment->notes ?: '-',
            ]),
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
