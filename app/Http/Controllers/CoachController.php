<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoachController extends Controller
{
    public function index(Request $request)
    {
        $totalCoaches = Coach::count();
        $totalActifs = Coach::where('statut', 'actif')->count();
        $totalInactifs = Coach::where('statut', 'inactif')->count();
        $salaireMoyen = Coach::where('statut', 'actif')->avg('salaire') ?? 0;

        return view('coaches.index', compact(
            'totalCoaches', 
            'totalActifs', 
            'totalInactifs',
            'salaireMoyen'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');

        $query = Coach::withCount(['activites' => function($q) {
            $q->where('statut', 'actif');
        }]);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($statut)) {
            $query->where('statut', $statut);
        }

        $totalRecords = $query->count();

        $coaches = $query->skip($start)
            ->take($length)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        foreach ($coaches as $index => $coach) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $coach->id,
                'nom_complet' => $coach->nom . ' ' . $coach->prenom,
                'specialite' => $coach->specialite ? 
                    '<span class="badge badge-info">' . $coach->specialite . '</span>' : 
                    '<span class="text-muted">N/A</span>',
                'contact' => '
                    <div>
                        <div><i class="fas fa-phone"></i> ' . $coach->telephone . '</div>
                        ' . ($coach->email ? '<div><i class="fas fa-envelope"></i> ' . $coach->email . '</div>' : '') . '
                    </div>',
                'salaire' => $coach->salaire ? 
                    number_format($coach->salaire, 2) . ' DH' : 
                    '<span class="text-muted">N/A</span>',
                'activites' => '
                    <div class="text-center">
                        <span class="badge badge-primary">' . $coach->activites_count . ' activité(s)</span>
                    </div>',
                'statut_badge' => $coach->statut == 'actif' ? 
                    '<span class="badge badge-success">Actif</span>' : 
                    '<span class="badge badge-danger">Inactif</span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $coach->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $coach->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $coach->id . '">
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
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'specialite' => 'nullable|string|max:100',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:coaches,email',
            'salaire' => 'nullable|numeric|min:0',
            'date_embauche' => 'nullable|date',
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
            Coach::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Coach créé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Coach $coach)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'specialite' => 'nullable|string|max:100',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:coaches,email,' . $coach->id,
            'salaire' => 'nullable|numeric|min:0',
            'date_embauche' => 'nullable|date',
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
            $coach->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Coach mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Coach $coach)
    {
        try {
            // Vérifier si le coach a des activités actives
            $activitesActives = $coach->activites()->where('statut', 'actif')->count();
            
            if ($activitesActives > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: coach a des activités actives'
                ], 400);
            }

            $coach->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Coach supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}