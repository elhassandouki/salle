<?php

namespace App\Http\Controllers;

use App\Models\Pointage;
use App\Models\Abonne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PointageController extends Controller
{
    public function index(Request $request)
    {
        $totalPointages = Pointage::count();
        $pointagesAujourdhui = Pointage::today()->count();
        $entreesAujourdhui = Pointage::today()->entrees()->count();
        $sortiesAujourdhui = Pointage::today()->sorties()->count();
        
        $abonnes = Abonne::whereNotNull('uid')
            ->whereHas('abonnements', function($query) {
                $query->where('statut', 'actif');
            })
            ->get();

        return view('pointages.index', compact(
            'totalPointages', 
            'pointagesAujourdhui', 
            'entreesAujourdhui',
            'sortiesAujourdhui',
            'abonnes'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $type = $request->input('filters.type');
        $date = $request->input('filters.date');
        $abonneId = $request->input('filters.abonne_id');
        $synced = $request->input('filters.synced');

        $query = Pointage::with('abonne');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('abonne', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%")
                       ->orWhere('prenom', 'LIKE', "%{$search}%")
                       ->orWhere('cin', 'LIKE', "%{$search}%")
                       ->orWhere('uid', 'LIKE', "%{$search}%");
                });
            });
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($date)) {
            $query->whereDate('date_pointage', $date);
        }

        if (!empty($abonneId)) {
            $query->where('abonne_id', $abonneId);
        }

        if ($synced !== null && $synced !== '') {
            $query->where('synced', $synced);
        }

        $totalRecords = $query->count();

        $pointages = $query->skip($start)
            ->take($length)
            ->orderBy('date_pointage', 'desc')
            ->get();

        $data = [];
        foreach ($pointages as $index => $pointage) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $pointage->id,
                'abonne' => $pointage->abonne ? 
                    $pointage->abonne->nom . ' ' . $pointage->abonne->prenom : 
                    '<span class="text-muted">Abonné inconnu</span>',
                'uid' => $pointage->uid ?? 
                    '<span class="text-muted">N/A</span>',
                'date' => '
                    <div class="text-center">
                        <div>' . $pointage->date_only . '</div>
                        <strong>' . $pointage->heure . '</strong>
                    </div>',
                'type' => '
                    <span class="badge badge-' . $pointage->couleur_type . '">
                        ' . $pointage->type_text . '
                    </span>',
                'synced_badge' => $pointage->synced ? 
                    '<span class="badge badge-success"><i class="fas fa-check"></i> Synced</span>' : 
                    '<span class="badge badge-warning"><i class="fas fa-times"></i> Not Synced</span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $pointage->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $pointage->id . '">
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
            'abonne_id' => 'required|exists:abonnes,id',
            'uid' => 'nullable|string|max:50',
            'date_pointage' => 'required|date',
            'type' => 'required|in:entree,sortie'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si l'abonné a un abonnement actif
            $abonne = Abonne::findOrFail($request->abonne_id);
            $hasActiveAbonnement = $abonne->abonnements()
                ->where('statut', 'actif')
                ->exists();

            if (!$hasActiveAbonnement) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'abonné n\'a pas d\'abonnement actif'
                ], 400);
            }

            $pointage = Pointage::create([
                'abonne_id' => $request->abonne_id,
                'uid' => $request->uid ?? $abonne->uid,
                'date_pointage' => $request->date_pointage,
                'type' => $request->type,
                'synced' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pointage enregistré avec succès',
                'pointage' => $pointage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importZKTeco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pointages' => 'required|array',
            'pointages.*.uid' => 'required|string',
            'pointages.*.date_pointage' => 'required|date',
            'pointages.*.type' => 'required|in:entree,sortie'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($request->pointages as $pointageData) {
                // Chercher l'abonné par UID
                $abonne = Abonne::where('uid', $pointageData['uid'])->first();

                if (!$abonne) {
                    $failed++;
                    $errors[] = 'UID ' . $pointageData['uid'] . ' non trouvé';
                    continue;
                }

                // Vérifier si le pointage existe déjà
                $exists = Pointage::where('uid', $pointageData['uid'])
                    ->where('date_pointage', $pointageData['date_pointage'])
                    ->where('type', $pointageData['type'])
                    ->exists();

                if ($exists) {
                    continue; // Pointage déjà importé
                }

                // Créer le pointage
                Pointage::create([
                    'abonne_id' => $abonne->id,
                    'uid' => $pointageData['uid'],
                    'date_pointage' => $pointageData['date_pointage'],
                    'type' => $pointageData['type'],
                    'synced' => true
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import terminé',
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Pointage $pointage)
    {
        try {
            $pointage->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Pointage supprimé avec succès'
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
        
        $pointages = Pointage::whereBetween('date_pointage', [$dateDebut, $dateFin])->get();
        
        $total = $pointages->count();
        $entrees = $pointages->where('type', 'entree')->count();
        $sorties = $pointages->where('type', 'sortie')->count();
        
        // Fréquentation par jour
        $parJour = $pointages->where('type', 'entree')
            ->groupBy(function($item) {
                return $item->date_pointage->format('Y-m-d');
            })
            ->map(function($items) {
                return $items->count();
            })
            ->sortKeys();
        
        // Heures de pointe
        $parHeure = $pointages->where('type', 'entree')
            ->groupBy(function($item) {
                return $item->date_pointage->format('H:00');
            })
            ->map(function($items) {
                return $items->count();
            })
            ->sortKeys();
        
        // Abonnés les plus actifs
        $abonnesActifs = $pointages->where('type', 'entree')
            ->groupBy('abonne_id')
            ->map(function($items, $abonneId) {
                $abonne = $items->first()->abonne;
                return [
                    'abonne' => $abonne,
                    'count' => $items->count()
                ];
            })
            ->sortByDesc('count')
            ->take(10);
        
        return response()->json([
            'success' => true,
            'total' => $total,
            'entrees' => $entrees,
            'sorties' => $sorties,
            'par_jour' => $parJour,
            'par_heure' => $parHeure,
            'abonnes_actifs' => $abonnesActifs,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
    }

    public function export(Request $request)
    {
        $query = Pointage::with('abonne');
        
        if ($request->has('date_debut') && $request->date_debut) {
            $query->where('date_pointage', '>=', $request->date_debut);
        }
        
        if ($request->has('date_fin') && $request->date_fin) {
            $query->where('date_pointage', '<=', $request->date_fin);
        }
        
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('abonne_id') && $request->abonne_id) {
            $query->where('abonne_id', $request->abonne_id);
        }
        
        $pointages = $query->orderBy('date_pointage', 'desc')->get();
        
        $fileName = 'pointages_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($pointages) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Date',
                'Heure',
                'Abonné',
                'UID',
                'Type',
                'Synchronisé'
            ], ';');
            
            // Données
            foreach ($pointages as $pointage) {
                fputcsv($file, [
                    $pointage->id,
                    $pointage->date_pointage->format('d/m/Y'),
                    $pointage->date_pointage->format('H:i:s'),
                    $pointage->abonne ? $pointage->abonne->nom . ' ' . $pointage->abonne->prenom : 'N/A',
                    $pointage->uid ?? '',
                    $pointage->type_text,
                    $pointage->synced ? 'Oui' : 'Non'
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * API pour la synchronisation avec ZKTeco
     */
    public function syncZKTecoAPI(Request $request)
    {
        // Cette méthode reçoit les données de ZKTeco via API
        // À adapter selon votre configuration ZKTeco
        
        try {
            $data = $request->all();
            
            // Logique d'import des pointages depuis ZKTeco
            // À implémenter selon votre SDK ZKTeco
            
            return response()->json([
                'success' => true,
                'message' => 'Données reçues avec succès',
                'data_received' => count($data)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}