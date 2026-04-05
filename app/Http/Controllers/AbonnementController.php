<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\Service;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AbonnementController extends Controller
{
    public function index(Request $request)
    {
        $totalAbonnements = Subscription::count();
        $totalActifs = Subscription::where('statut', 'actif')->count();
        $totalExpires = Subscription::whereIn('statut', $this->expiredStatuses())->count();
        $totalExpirant = Subscription::where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays(7))
            ->count();

        $activites = Service::where('type', 'activite')
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();

        $abonnes = Abonne::orderBy('nom')->orderBy('prenom')->get();

        return view('abonnements.index', compact(
            'totalAbonnements',
            'totalActifs',
            'totalExpires',
            'totalExpirant',
            'activites',
            'abonnes'
        ));
    }

    public function getData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $serviceId = $request->input('filters.activite_id', $request->input('filters.service_id'));
        $type = $request->input('filters.type');
        $dateDebut = $request->input('filters.date_debut');
        $dateFin = $request->input('filters.date_fin');

        $query = Subscription::with(['abonne', 'service'])
            ->select('subscriptions.*');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('abonne', function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('cin', 'like', "%{$search}%");
                })->orWhereHas('service', function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%")
                        ->where('type', 'activite');
                });
            });
        }

        if (! empty($statut)) {
            if ($statut === 'expire') {
                $query->whereIn('statut', $this->expiredStatuses());
            } else {
                $query->where('statut', $statut);
            }
        }

        if (! empty($serviceId)) {
            $query->where('service_id', $serviceId);
        }

        if (! empty($type)) {
            $query->where('type_abonnement', $type);
        }

        if (! empty($dateDebut)) {
            $query->whereDate('date_debut', '>=', $dateDebut);
        }

        if (! empty($dateFin)) {
            $query->whereDate('date_fin', '<=', $dateFin);
        }

        $totalRecords = Subscription::count();
        $recordsFiltered = (clone $query)->count();

        $abonnements = $query->orderByDesc('created_at')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];

        foreach ($abonnements as $index => $abonnement) {
            $joursRestants = (int) $abonnement->jours_restants;

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $abonnement->id,
                'abonne' => e($abonnement->abonne->nom . ' ' . $abonnement->abonne->prenom),
                'activite' => $abonnement->service && $abonnement->service->type === 'activite'
                    ? '<span class="badge" style="background-color:' . e($abonnement->service->couleur ?? '#007bff') . '">' . e($abonnement->service->nom) . '</span>'
                    : '<span class="badge badge-secondary">N/A</span>',
                'type' => ucfirst((string) $abonnement->type_abonnement),
                'dates' => '
                    <div class="text-center">
                        <div><small>Debut:</small> ' . optional($abonnement->date_debut)->format('d/m/Y') . '</div>
                        <div><small>Fin:</small> ' . optional($abonnement->date_fin)->format('d/m/Y') . '</div>
                    </div>',
                'montant' => '
                    <div class="text-right">
                        <div><strong>' . number_format((float) $abonnement->montant_total, 2) . ' DH</strong></div>
                        <div><small>Paye:</small> ' . number_format((float) $abonnement->montant_paye, 2) . ' DH</div>
                        <div><small>Reste:</small> ' . number_format((float) $abonnement->reste, 2) . ' DH</div>
                    </div>',
                'jours_restants' => '
                    <div class="text-center">
                        ' . ($joursRestants > 0
                            ? '<span class="badge badge-' . ($joursRestants <= 7 ? 'warning' : 'success') . '">' . $joursRestants . ' jour(s)</span>'
                            : '<span class="badge badge-danger">Expire</span>') . '
                    </div>',
                'statut_badge' => '
                    <span class="badge badge-' . e($abonnement->statut_couleur) . '">
                        ' . e(ucfirst($this->displayStatus($abonnement->statut))) . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $abonnement->id . '" title="Voir">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-success paiement-btn" data-id="' . $abonnement->id . '" title="Paiement">
                            <i class="fas fa-money-bill"></i>
                        </button>
                        <button class="btn btn-warning renouveler-btn" data-id="' . $abonnement->id . '" title="Renouveler">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $abonnement->id . '" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'abonne_id' => 'required|exists:abonnes,id',
            'service_id' => 'required|exists:services,id',
            'type_abonnement' => 'required|in:mensuel,trimestriel,annuel',
            'date_debut' => 'required|date',
            'montant' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expire,suspendu',
            'remise' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'montant_paye_initial' => 'nullable|numeric|min:0',
            'mode_paiement' => 'nullable|in:especes,carte,cheque,virement',
            'date_paiement' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes_paiement' => 'nullable|string',
            'assurance_montant' => 'nullable|numeric|min:0',
            'assurance_montant_paye_initial' => 'nullable|numeric|min:0',
            'assurance_type_abonnement' => 'nullable|in:mensuel,trimestriel,semestriel,annuel',
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasActivityPayment = (float) ($request->montant_paye_initial ?? 0) > 0;
            $hasAssurancePayment = (float) ($request->assurance_montant_paye_initial ?? 0) > 0;

            if (($hasActivityPayment || $hasAssurancePayment) && ! $request->filled('mode_paiement')) {
                $validator->errors()->add('mode_paiement', 'Le mode de paiement est obligatoire.');
            }

            if (($hasActivityPayment || $hasAssurancePayment) && ! $request->filled('date_paiement')) {
                $validator->errors()->add('date_paiement', 'La date de paiement est obligatoire.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            \DB::beginTransaction();

            $activite = Service::where('type', 'activite')->findOrFail($request->service_id);
            $assurance = (float) ($request->assurance_montant ?? 0) > 0;

            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin = $this->calculateEndDate($dateDebut, $request->type_abonnement);
            $montant = (float) $request->montant;
            $remise = (float) ($request->remise ?? 0);
            $montantTotal = max(0, $montant - $remise);
            $montantPayeInitial = min((float) ($request->montant_paye_initial ?? 0), $montantTotal);

            Subscription::where('abonne_id', $request->abonne_id)
                ->where('statut', 'actif')
                ->whereHas('service', function ($query) {
                    $query->where('type', 'activite');
                })
                ->update(['statut' => 'expire']);

            $abonnement = Subscription::create([
                'abonne_id' => $request->abonne_id,
                'service_id' => $activite->id,
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $montant,
                'remise' => $remise,
                'montant_total' => $montantTotal,
                'montant_paye' => $montantPayeInitial,
                'reste' => max(0, $montantTotal - $montantPayeInitial),
                'statut' => $this->normalizeStatus($request->statut),
                'notes' => $request->notes,
            ]);

            $this->createInitialPayment($abonnement, $montantPayeInitial, $request, 'Paiement activite');

            $assuranceSubscription = null;

            if ($assurance) {
                $assuranceService = $this->resolveGenericAssuranceService();
                $existingAssurance = Subscription::where('abonne_id', $request->abonne_id)
                    ->whereHas('service', function ($query) {
                        $query->where('type', 'assurance');
                    })
                    ->where('statut', 'actif')
                    ->exists();

                if ($existingAssurance) {
                    throw new \RuntimeException("L'abonne a deja cette assurance active.");
                }

                $assuranceMontant = (float) ($request->assurance_montant ?? 0);
                $assuranceMontantPaye = min((float) ($request->assurance_montant_paye_initial ?? 0), $assuranceMontant);
                $assuranceType = $request->assurance_type_abonnement ?: 'trimestriel';

                $assuranceSubscription = Subscription::create([
                    'abonne_id' => $request->abonne_id,
                    'service_id' => $assuranceService->id,
                    'type_abonnement' => $assuranceType,
                    'date_debut' => $dateDebut,
                    'date_fin' => $this->calculateInsuranceEndDate($dateDebut, $assuranceType),
                    'montant' => $assuranceMontant,
                    'remise' => 0,
                    'montant_total' => $assuranceMontant,
                    'montant_paye' => $assuranceMontantPaye,
                    'reste' => max(0, $assuranceMontant - $assuranceMontantPaye),
                    'statut' => $this->normalizeStatus($request->statut),
                    'notes' => trim("Assurance {$assuranceType}\n" . ($request->notes ?? '')),
                ]);

                $this->createInitialPayment($assuranceSubscription, $assuranceMontantPaye, $request, 'Paiement assurance');
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => $assuranceSubscription
                    ? 'Abonnement, paiement et assurance enregistres avec succes'
                    : 'Abonnement cree avec succes',
                'abonnement' => $abonnement->load(['abonne', 'service', 'paiements']),
                'assurance' => $assuranceSubscription?->load(['abonne', 'service', 'paiements']),
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,expire,suspendu',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $subscription->update([
                'statut' => $this->normalizeStatus($request->statut),
                'date_fin' => $request->date_fin,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement mis a jour avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['abonne', 'service', 'paiements']);

        return response()->json([
            'success' => true,
            'abonnement' => [
                'id' => $subscription->id,
                'abonne' => $subscription->abonne->nom . ' ' . $subscription->abonne->prenom,
                'abonne_cin' => $subscription->abonne->cin,
                'service' => $subscription->service->nom ?? 'N/A',
                'type' => ucfirst((string) $subscription->type_abonnement),
                'date_debut' => optional($subscription->date_debut)->format('d/m/Y'),
                'date_fin' => optional($subscription->date_fin)->format('d/m/Y'),
                'montant' => number_format((float) $subscription->montant_total, 2) . ' DH',
                'montant_brut' => number_format((float) $subscription->montant, 2) . ' DH',
                'remise' => number_format((float) $subscription->remise, 2) . ' DH',
                'montant_paye' => number_format((float) $subscription->montant_paye, 2) . ' DH',
                'reste' => number_format((float) $subscription->reste, 2) . ' DH',
                'statut' => ucfirst($this->displayStatus($subscription->statut)),
                'paiements_count' => $subscription->paiements->count(),
                'jours_restants' => $subscription->jours_restants,
                'notes' => $subscription->notes,
                'created_at' => optional($subscription->created_at)->format('d/m/Y H:i'),
                'updated_at' => optional($subscription->updated_at)->format('d/m/Y H:i'),
                'paiements' => $subscription->paiements
                    ->sortByDesc('date_paiement')
                    ->values()
                    ->map(function ($paiement) {
                        return [
                            'montant' => number_format((float) $paiement->montant, 2) . ' DH',
                            'mode_paiement' => ucfirst((string) $paiement->mode_paiement),
                            'date_paiement' => optional($paiement->date_paiement)->format('d/m/Y H:i'),
                            'reference' => $paiement->reference,
                            'notes' => $paiement->notes,
                        ];
                    }),
            ],
        ]);
    }

    public function edit(Subscription $subscription)
    {
        $subscription->load(['abonne', 'service']);

        return response()->json([
            'success' => true,
            'abonnement' => [
                'id' => $subscription->id,
                'abonne_id' => $subscription->abonne_id,
                'service_id' => $subscription->service_id,
                'type_abonnement' => $subscription->type_abonnement,
                'date_debut' => optional($subscription->date_debut)->format('Y-m-d'),
                'date_fin' => optional($subscription->date_fin)->format('Y-m-d'),
                'montant' => $subscription->montant,
                'remise' => $subscription->remise,
                'statut' => $this->normalizeStatus($subscription->statut),
                'auto_renew' => $subscription->auto_renew,
                'notes' => $subscription->notes,
            ],
            'services' => Service::where('type', 'activite')->where('statut', 'actif')->get(),
            'abonnes' => Abonne::orderBy('nom')->orderBy('prenom')->get(),
        ]);
    }

    public function renew(Request $request, Subscription $subscription)
    {
        try {
            \DB::beginTransaction();
            $subscription->update(['statut' => 'expire']);

            $dateDebut = Carbon::parse($subscription->date_fin)->addDay();
            $dateFin = $this->calculateEndDate($dateDebut, $subscription->type_abonnement);

            $nouvelAbonnement = Subscription::create([
                'abonne_id' => $subscription->abonne_id,
                'service_id' => $subscription->service_id,
                'type_abonnement' => $subscription->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $subscription->montant,
                'remise' => $subscription->remise,
                'montant_total' => $subscription->montant_total,
                'montant_paye' => 0,
                'reste' => $subscription->montant_total,
                'statut' => 'actif',
                'notes' => $subscription->notes,
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelle avec succes',
                'abonnement' => $nouvelAbonnement,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Subscription $subscription)
    {
        try {
            if ($subscription->paiements()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: abonnement a des paiements associes',
                ], 400);
            }

            $subscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement supprime avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changeStatus(Request $request, Subscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,expire,suspendu',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $subscription->update(['statut' => $this->normalizeStatus($request->statut)]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis a jour avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = Subscription::with(['abonne', 'service']);

        if ($request->filled('statut')) {
            if ($request->statut === 'expire') {
                $query->whereIn('statut', $this->expiredStatuses());
            } else {
                $query->where('statut', $request->statut);
            }
        }

        if ($request->filled('activite_id')) {
            $query->where('service_id', $request->activite_id);
        }

        if ($request->filled('type')) {
            $query->where('type_abonnement', $request->type);
        }

        $subscriptions = $query->orderByDesc('created_at')->get();
        $fileName = 'subscriptions_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($subscriptions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Abonne',
                'Activite',
                'Type',
                'Date debut',
                'Date fin',
                'Montant total',
                'Montant paye',
                'Reste',
                'Statut',
            ], ';');

            foreach ($subscriptions as $subscription) {
                fputcsv($file, [
                    $subscription->id,
                    $subscription->abonne->nom . ' ' . $subscription->abonne->prenom,
                    $subscription->service->nom ?? '',
                    $subscription->type_abonnement,
                    optional($subscription->date_debut)->format('d/m/Y'),
                    optional($subscription->date_fin)->format('d/m/Y'),
                    $subscription->montant_total,
                    $subscription->montant_paye,
                    $subscription->reste,
                    $this->displayStatus($subscription->statut),
                ], ';');
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    protected function calculateEndDate(Carbon $dateDebut, string $type): Carbon
    {
        return match ($type) {
            'mensuel' => $dateDebut->copy()->addMonth(),
            'trimestriel' => $dateDebut->copy()->addMonths(3),
            'annuel' => $dateDebut->copy()->addYear(),
            default => $dateDebut->copy()->addMonth(),
        };
    }

    protected function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©' => 'expire',
            default => $status ?: 'actif',
        };
    }

    protected function displayStatus(?string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'expire' => 'expire',
            default => (string) $this->normalizeStatus($status),
        };
    }

    protected function expiredStatuses(): array
    {
        return ['expire', 'expiré', 'expirأ©', 'expirط£آ©', 'expirط·آ£ط¢آ©'];
    }

    protected function resolveGenericAssuranceService(): Service
    {
        return Service::firstOrCreate(
            ['type' => 'assurance', 'nom' => 'Assurance'],
            [
                'description' => 'Service generique pour les assurances',
                'prix_mensuel' => 0,
                'prix_trimestriel' => 0,
                'prix_annuel' => 0,
                'statut' => 'actif',
                'couleur' => '#17a2b8',
            ]
        );
    }

    protected function calculateInsuranceEndDate(Carbon $dateDebut, string $type): Carbon
    {
        return match ($type) {
            'mensuel' => $dateDebut->copy()->addMonth(),
            'trimestriel' => $dateDebut->copy()->addMonths(3),
            'semestriel' => $dateDebut->copy()->addMonths(6),
            'annuel' => $dateDebut->copy()->addYear(),
            default => $dateDebut->copy()->addMonths(3),
        };
    }

    protected function createInitialPayment(Subscription $subscription, float $montant, Request $request, string $label): void
    {
        if ($montant <= 0) {
            return;
        }

        $subscription->paiements()->create([
            'montant' => $montant,
            'mode_paiement' => $request->mode_paiement,
            'date_paiement' => $request->date_paiement,
            'reference' => $request->reference,
            'notes' => trim($label . PHP_EOL . ($request->notes_paiement ?? '')),
        ]);
    }
}
