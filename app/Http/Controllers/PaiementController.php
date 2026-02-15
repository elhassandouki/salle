<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Abonnement;
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
        
        $abonnements = Abonnement::where('statut', 'actif')
            ->with('abonne', 'activite')
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

        $query = Paiement::with(['abonnement.abonne', 'abonnement.activite']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('abonnement.abonne', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%")
                       ->orWhere('prenom', 'LIKE', "%{$search}%")
                       ->orWhere('cin', 'LIKE', "%{$search}%");
                })
                ->orWhere('reference', 'LIKE', "%{$search}%")
                ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($mode)) {
            $query->where('mode_paiement', $mode);
        }

        if (!empty($dateDebut)) {
            $query->where('date_paiement', '>=', $dateDebut);
        }

        if (!empty($dateFin)) {
            $query->where('date_paiement', '<=', $dateFin);
        }

        if (!empty($montantMin)) {
            $query->where('montant', '>=', $montantMin);
        }

        if (!empty($montantMax)) {
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
                'abonne' => $paiement->abonnement->abonne->nom . ' ' . 
                           $paiement->abonnement->abonne->prenom,
                'activite' => $paiement->abonnement->activite->nom,
                'montant' => '
                    <div class="text-right font-weight-bold">
                        ' . number_format($paiement->montant, 2) . ' DH
                    </div>',
                'mode' => '
                    <span class="badge badge-' . $paiement->couleur_mode . '">
                        ' . $paiement->mode_paiement_text . '
                    </span>',
                'reference' => $paiement->reference ?? 
                    '<span class="text-muted">N/A</span>',
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
                    </div>'
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'abonnement_id' => 'required|exists:abonnements,id',
            'montant' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:especes,carte,cheque,virement',
            'date_paiement' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $paiement = Paiement::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'paiement' => $paiement
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
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
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $paiement->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Paiement $paiement)
    {
        try {
            $paiement->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function statistiques(Request $request)
    {
        $dateDebut = $request->input('date_debut', now()->startOfMonth());
        $dateFin = $request->input('date_fin', now()->endOfMonth());
        
        $paiements = Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->get();
        
        $total = $paiements->sum('montant');
        
        $parMode = $paiements->groupBy('mode_paiement')->map(function($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('montant'),
                'pourcentage' => round(($items->sum('montant') / $items->sum('montant') * 100), 2)
            ];
        });
        
        $parJour = $paiements->groupBy(function($item) {
                return $item->date_paiement->format('Y-m-d');
            })
            ->map(function($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('montant')
                ];
            })
            ->sortKeys();
        
        return response()->json([
            'success' => true,
            'total' => $total,
            'par_mode' => $parMode,
            'par_jour' => $parJour,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
    }

    public function export(Request $request)
    {
        $query = Paiement::with(['abonnement.abonne', 'abonnement.activite']);
        
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
        
        $callback = function() use ($paiements) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Date',
                'Abonné',
                'Activité',
                'Montant (DH)',
                'Mode de paiement',
                'Référence',
                'Notes'
            ], ';');
            
            // Données
            foreach ($paiements as $paiement) {
                fputcsv($file, [
                    $paiement->id,
                    $paiement->date_paiement->format('d/m/Y H:i'),
                    $paiement->abonnement->abonne->nom . ' ' . $paiement->abonnement->abonne->prenom,
                    $paiement->abonnement->activite->nom,
                    $paiement->montant,
                    $paiement->mode_paiement_text,
                    $paiement->reference ?? '',
                    $paiement->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}