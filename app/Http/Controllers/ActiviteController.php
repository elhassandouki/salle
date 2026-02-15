<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActiviteController extends Controller
{
    public function index(Request $request)
    {
        $totalActivites = Activite::count();
        $totalActives = Activite::where('statut', 'actif')->count();
        $totalInactives = Activite::where('statut', 'inactif')->count();
        $totalAbonnesActifs = DB::table('abonnements')
            ->where('statut', 'actif')
            ->count();
        
        $coaches = Coach::where('statut', 'actif')->get();

        return view('activites.index', compact(
            'totalActivites', 
            'totalActives', 
            'totalInactives',
            'totalAbonnesActifs',
            'coaches'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $coachId = $request->input('filters.coach_id');

        $query = Activite::with('coach')
            ->withCount(['abonnements' => function($q) {
                $q->where('statut', 'actif');
            }]);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($statut)) {
            $query->where('statut', $statut);
        }

        if (!empty($coachId)) {
            $query->where('coach_id', $coachId);
        }

        $totalRecords = $query->count();

        $activites = $query->skip($start)
            ->take($length)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        foreach ($activites as $index => $activite) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $activite->id,
                'nom' => $activite->nom,
                'description' => $activite->description ? 
                    '<small class="text-muted">' . Str::limit($activite->description, 50) . '</small>' : 
                    '<span class="text-muted">N/A</span>',
                'coach' => $activite->coach ? 
                    $activite->coach->nom . ' ' . $activite->coach->prenom : 
                    '<span class="text-muted">Non assigné</span>',
                'prix' => '
                    <div class="text-center">
                        <div>M: ' . $activite->prix_mensuel . ' DH</div>
                        <div>T: ' . $activite->prix_trimestriel . ' DH</div>
                        <div>A: ' . $activite->prix_annuel . ' DH</div>
                    </div>',
                'capacite' => '
                    <div class="text-center">
                        <div>' . $activite->abonnements_count . ' / ' . $activite->capacite_max . '</div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar ' . ($activite->abonnements_count >= $activite->capacite_max ? 'bg-danger' : 'bg-success') . '" 
                                 style="width: ' . min(100, ($activite->abonnements_count / $activite->capacite_max) * 100) . '%">
                            </div>
                        </div>
                    </div>',
                'statut_badge' => $activite->statut == 'actif' ? 
                    '<span class="badge badge-success">Actif</span>' : 
                    '<span class="badge badge-danger">Inactif</span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $activite->id . '" title="Voir">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $activite->id . '" title="Éditer">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $activite->id . '" title="Supprimer">
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
            'nom' => 'required|string|max:100|unique:activites,nom',
            'description' => 'nullable|string',
            'coach_id' => 'nullable|exists:coaches,id',
            'prix_mensuel' => 'required|numeric|min:0',
            'prix_trimestriel' => 'required|numeric|min:0',
            'prix_annuel' => 'required|numeric|min:0',
            'capacite_max' => 'required|integer|min:1',
            'couleur' => 'nullable|string|max:7',
            'statut' => 'required|in:actif,inactif'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $activite = Activite::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Activité créée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Activite $activite)
    {
        $activite->load(['coach', 'abonnements.abonne']);
        
        return response()->json([
            'success' => true,
            'data' => $activite
        ]);
    }

    public function update(Request $request, Activite $activite)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:activites,nom,' . $activite->id,
            'description' => 'nullable|string',
            'coach_id' => 'nullable|exists:coaches,id',
            'prix_mensuel' => 'required|numeric|min:0',
            'prix_trimestriel' => 'required|numeric|min:0',
            'prix_annuel' => 'required|numeric|min:0',
            'capacite_max' => 'required|integer|min:1',
            'couleur' => 'nullable|string|max:7',
            'statut' => 'required|in:actif,inactif'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $activite->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Activité mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Activite $activite)
    {
        try {
            // Vérifier s'il y a des abonnements actifs
            $abonnementsActifs = $activite->abonnements()->where('statut', 'actif')->count();
            
            if ($abonnementsActifs > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: activité a des abonnements actifs'
                ], 400);
            }

            $activite->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Activité supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}