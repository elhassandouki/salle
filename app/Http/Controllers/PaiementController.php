<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $totalPaiements = Paiement::count();
        $totalAujourdhui = Paiement::today()->sum('montant');
        $totalMois = Paiement::thisMonth()->sum('montant');
        $totalAnnee = Paiement::whereYear('date_paiement', now()->year)->sum('montant');

        $abonnements = Subscription::where('statut', 'actif')
            ->with('abonne', 'service')
            ->get();

        return view('paiements.index', compact(
            'totalPaiements',
            'totalAujourdhui',
            'totalMois',
            'totalAnnee',
            'abonnements'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $mode = $request->input('filters.mode');
        $dateDebut = $request->input('filters.date_debut');
        $dateFin = $request->input('filters.date_fin');
        $montantMin = $request->input('filters.montant_min');
        $montantMax = $request->input('filters.montant_max');

        $query = Paiement::with(['subscription.abonne', 'subscription.service']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('subscription.abonne', function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('cin', 'like', "%{$search}%");
                })->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($mode)) {
            $query->where('mode_paiement', $mode);
        }

        if (! empty($dateDebut)) {
            $query->where('date_paiement', '>=', $dateDebut);
        }

        if (! empty($dateFin)) {
            $query->where('date_paiement', '<=', $dateFin);
        }

        if (! empty($montantMin)) {
            $query->where('montant', '>=', $montantMin);
        }

        if (! empty($montantMax)) {
            $query->where('montant', '<=', $montantMax);
        }

        $totalRecords = $query->count();

        $paiements = $query->skip($start)
            ->take($length)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $data = [];
        foreach ($paiements as $index => $paiement) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $paiement->id,
                'abonne' => $paiement->subscription->abonne->nom . ' ' . $paiement->subscription->abonne->prenom,
                'activite' => $paiement->subscription->service->nom,
                'montant' => '
                    <div class="text-right font-weight-bold">
                        ' . number_format((float) $paiement->montant, 2) . ' DH
                    </div>',
                'mode' => '
                    <span class="badge badge-' . e($paiement->couleur_mode) . '">
                        ' . e($paiement->mode_paiement_text) . '
                    </span>',
                'reference' => $paiement->reference ?: '<span class="text-muted">N/A</span>',
                'date' => '
                    <div class="text-center">
                        <div>' . $paiement->date_paiement->format('d/m/Y') . '</div>
                        <small class="text-muted">' . $paiement->date_paiement->format('H:i') . '</small>
                    </div>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $paiement->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $paiement->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $paiement->id . '">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_id' => 'required|exists:subscriptions,id',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,carte,cheque,virement',
            'date_paiement' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paiement = Paiement::create($request->only([
                'subscription_id',
                'montant',
                'mode_paiement',
                'date_paiement',
                'reference',
                'notes',
            ]));

            $this->syncSubscriptionTotals((int) $request->subscription_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistre avec succes',
                'paiement' => $paiement,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Paiement $paiement)
    {
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,carte,cheque,virement',
            'date_paiement' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paiement->update($request->only([
                'montant',
                'mode_paiement',
                'date_paiement',
                'reference',
                'notes',
            ]));

            $this->syncSubscriptionTotals((int) $paiement->subscription_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement mis a jour avec succes',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Paiement $paiement)
    {
        $paiement->load(['subscription.abonne', 'subscription.service']);

        return response()->json([
            'success' => true,
            'paiement' => [
                'id' => $paiement->id,
                'abonne' => $paiement->subscription->abonne->nom . ' ' . $paiement->subscription->abonne->prenom,
                'service' => $paiement->subscription->service->nom ?? 'N/A',
                'montant' => number_format((float) $paiement->montant, 2) . ' DH',
                'mode_paiement' => $paiement->mode_paiement_text,
                'reference' => $paiement->reference,
                'date_paiement' => $paiement->date_paiement->format('d/m/Y H:i'),
                'notes' => $paiement->notes,
            ],
        ]);
    }

    public function edit(Paiement $paiement)
    {
        $paiement->load(['subscription']);

        return response()->json([
            'success' => true,
            'paiement' => [
                'id' => $paiement->id,
                'subscription_id' => $paiement->subscription_id,
                'montant' => $paiement->montant,
                'mode_paiement' => $paiement->mode_paiement,
                'date_paiement' => $paiement->date_paiement->format('Y-m-d\TH:i'),
                'reference' => $paiement->reference,
                'notes' => $paiement->notes,
            ],
            'subscriptions' => Subscription::with(['abonne', 'service'])->get(),
        ]);
    }

    public function destroy(Paiement $paiement)
    {
        try {
            DB::beginTransaction();

            $subscriptionId = (int) $paiement->subscription_id;
            $paiement->delete();
            $this->syncSubscriptionTotals($subscriptionId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprime avec succes',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function statistiques(Request $request)
    {
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now()->endOfMonth());

        $paiements = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->get();
        $total = $paiements->sum('montant');

        $parMode = $paiements->groupBy('mode_paiement')->map(function ($items) use ($total) {
            $modeTotal = $items->sum('montant');

            return [
                'count' => $items->count(),
                'total' => $modeTotal,
                'pourcentage' => $total > 0 ? round(($modeTotal / $total) * 100, 2) : 0,
            ];
        });

        $parJour = $paiements->groupBy(function ($item) {
            return $item->date_paiement->format('Y-m-d');
        })->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('montant'),
            ];
        })->sortKeys();

        return response()->json([
            'success' => true,
            'total' => $total,
            'par_mode' => $parMode,
            'par_jour' => $parJour,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
    }

    public function export(Request $request)
    {
        $query = Paiement::with(['subscription.abonne', 'subscription.service']);

        if ($request->has('date_debut') && $request->date_debut) {
            $query->where('date_paiement', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && $request->date_fin) {
            $query->where('date_paiement', '<=', $request->date_fin);
        }

        if ($request->has('mode') && $request->mode) {
            $query->where('mode_paiement', $request->mode);
        }

        $paiements = $query->orderBy('date_paiement', 'desc')->get();
        $fileName = 'paiements_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($paiements) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Date',
                'Abonne',
                'Activite',
                'Montant (DH)',
                'Mode de paiement',
                'Reference',
                'Notes',
            ], ';');

            foreach ($paiements as $paiement) {
                fputcsv($file, [
                    $paiement->id,
                    $paiement->date_paiement->format('d/m/Y H:i'),
                    $paiement->subscription->abonne->nom . ' ' . $paiement->subscription->abonne->prenom,
                    $paiement->subscription->service->nom,
                    $paiement->montant,
                    $paiement->mode_paiement_text,
                    $paiement->reference ?? '',
                    $paiement->notes ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function syncSubscriptionTotals(int $subscriptionId): void
    {
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return;
        }

        $montantPaye = (float) $subscription->paiements()->sum('montant');
        $montantTotal = (float) ($subscription->montant_total ?? $subscription->montant ?? 0);

        $subscription->update([
            'montant_paye' => $montantPaye,
            'reste' => max(0, $montantTotal - $montantPaye),
        ]);
    }
}
