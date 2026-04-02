<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActiviteController extends Controller
{
    public function index(Request $request)
    {
        $totalActivites = Service::where('type', 'activite')->count();
        $totalActives = Service::where('type', 'activite')->where('statut', 'actif')->count();
        $totalInactives = Service::where('type', 'activite')->where('statut', 'inactif')->count();
        $totalAbonnesActifs = DB::table('subscriptions')
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

        $query = Service::where('type', 'activite')->with('coach')
            ->withCount(['subscriptions' => function($q) {
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
                        <div>' . $activite->subscriptions_count . ' / ' . $activite->capacite_max . '</div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar ' . ($activite->subscriptions_count >= $activite->capacite_max ? 'bg-danger' : 'bg-success') . '" 
                                 style="width: ' . min(100, ($activite->subscriptions_count / $activite->capacite_max) * 100) . '%">
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
            'nom' => 'required|string|max:100|unique:services,nom',
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
            $data = $request->all();
            $data['type'] = 'activite';
            $activite = Service::create($data);
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

    public function show(Service $activite)
    {
        $activite->load(['coach', 'subscriptions.abonne']);
        
        return response()->json([
            'success' => true,
            'data' => $activite
        ]);
    }

    public function edit(Service $activite)
    {
        return response()->json([
            'success' => true,
            'data' => $activite,
            'coaches' => Coach::where('statut', 'actif')->get(),
        ]);
    }

    public function update(Request $request, Service $activite)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:services,nom,' . $activite->id,
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
            $data = $request->all();
            $data['type'] = 'activite';
            $activite->update($data);
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

    public function destroy(Service $activite)
    {
        try {
            // Vérifier s'il y a des subscriptions actives
            $subscriptionsActives = $activite->subscriptions()->where('statut', 'actif')->count();
            
            if ($subscriptionsActives > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: activité a des subscriptions actives'
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

    public function getPrix(Service $activite)
    {
        return response()->json([
            'success' => true,
            'prix' => $activite->prix,
        ]);
    }

    public function export(Request $request)
    {
        $query = Service::where('type', 'activite')->with('coach')
            ->withCount(['subscriptions' => function ($q) {
                $q->where('statut', 'actif');
            }]);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }

        $activites = $query->orderBy('nom')->get();
        $fileName = 'activites_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($activites) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Nom',
                'Description',
                'Coach',
                'Prix Mensuel',
                'Prix Trimestriel',
                'Prix Annuel',
                'Capacite',
                'Abonnes Actifs',
                'Statut',
            ], ';');

            foreach ($activites as $activite) {
                fputcsv($file, [
                    $activite->id,
                    $activite->nom,
                    $activite->description ?? '',
                    $activite->coach ? $activite->coach->nom . ' ' . $activite->coach->prenom : '',
                    $activite->prix_mensuel,
                    $activite->prix_trimestriel,
                    $activite->prix_annuel,
                    $activite->capacite_max,
                    $activite->subscriptions_count,
                    $activite->statut,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
