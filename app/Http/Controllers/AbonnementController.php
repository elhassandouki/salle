<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Abonne;
use App\Models\Activite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AbonnementController extends Controller
{
    public function index(Request $request)
    {
        $totalAbonnements = Abonnement::count();
        $totalActifs = Abonnement::where('statut', 'actif')->count();
        $totalExpires = Abonnement::where('statut', 'expiré')->count();
        $totalExpirant = Abonnement::where('statut', 'actif')
            ->where('date_fin', '<=', now()->addDays(7))
            ->count();
        
        $activites = Activite::where('statut', 'actif')->get();
        $abonnes = Abonne::all();

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
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $activiteId = $request->input('filters.activite_id');
        $type = $request->input('filters.type');
        $dateDebut = $request->input('filters.date_debut');
        $dateFin = $request->input('filters.date_fin');

        $query = Abonnement::with(['abonne', 'activite'])
            ->select('abonnements.*')
            ->addSelect(DB::raw('DATEDIFF(date_fin, CURDATE()) as jours_restants'));

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('abonne', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%")
                       ->orWhere('prenom', 'LIKE', "%{$search}%")
                       ->orWhere('cin', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('activite', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%");
                });
            });
        }

        if (!empty($statut)) {
            $query->where('statut', $statut);
        }

        if (!empty($activiteId)) {
            $query->where('activite_id', $activiteId);
        }

        if (!empty($type)) {
            $query->where('type_abonnement', $type);
        }

        if (!empty($dateDebut)) {
            $query->where('date_debut', '>=', $dateDebut);
        }

        if (!empty($dateFin)) {
            $query->where('date_fin', '<=', $dateFin);
        }

        $totalRecords = $query->count();

        $abonnements = $query->skip($start)
            ->take($length)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        foreach ($abonnements as $index => $abonnement) {
            $joursRestants = $abonnement->jours_restants ?? 
                \Carbon\Carbon::parse($abonnement->date_fin)->diffInDays(now());
            
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $abonnement->id,
                'abonne' => $abonnement->abonne->nom . ' ' . $abonnement->abonne->prenom,
                'activite' => '<span class="badge" style="background-color:' . ($abonnement->activite->couleur ?? '#007bff') . '">' . 
                              $abonnement->activite->nom . '</span>',
                'type' => ucfirst($abonnement->type_abonnement),
                'dates' => '
                    <div class="text-center">
                        <div><small>Début:</small> ' . $abonnement->date_debut->format('d/m/Y') . '</div>
                        <div><small>Fin:</small> ' . $abonnement->date_fin->format('d/m/Y') . '</div>
                    </div>',
                'montant' => number_format($abonnement->montant, 2) . ' DH',
                'jours_restants' => '
                    <div class="text-center">
                        ' . ($joursRestants > 0 ? 
                            '<span class="badge badge-' . ($joursRestants <= 7 ? 'warning' : 'success') . '">' . 
                            $joursRestants . ' jour(s)</span>' : 
                            '<span class="badge badge-danger">Expiré</span>') . '
                    </div>',
                'statut_badge' => '
                    <span class="badge badge-' . $abonnement->statut_couleur . '">
                        ' . ucfirst($abonnement->statut) . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $abonnement->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $abonnement->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-success paiement-btn" data-id="' . $abonnement->id . '" title="Paiement">
                            <i class="fas fa-money-bill"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $abonnement->id . '">
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
            'activite_id' => 'required|exists:activites,id',
            'type_abonnement' => 'required|in:mensuel,trimestriel,annuel',
            'date_debut' => 'required|date',
            'montant' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expiré,suspendu'
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

            $activite = Activite::findOrFail($request->activite_id);
            
            // Calculer la date de fin
            $dateDebut = \Carbon\Carbon::parse($request->date_debut);
            $dateFin = $dateDebut->copy();
            
            switch ($request->type_abonnement) {
                case 'mensuel':
                    $dateFin->addMonth();
                    break;
                case 'trimestriel':
                    $dateFin->addMonths(3);
                    break;
                case 'annuel':
                    $dateFin->addYear();
                    break;
            }

            // Désactiver les anciens abonnements de cet abonné
            Abonnement::where('abonne_id', $request->abonne_id)
                ->where('statut', 'actif')
                ->update(['statut' => 'expiré']);

            // Créer le nouvel abonnement
            $abonnement = Abonnement::create([
                'abonne_id' => $request->abonne_id,
                'activite_id' => $request->activite_id,
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $request->montant,
                'statut' => $request->statut,
                'zk_sync' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement créé avec succès',
                'abonnement' => $abonnement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Abonnement $abonnement)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,expiré,suspendu',
            'date_fin' => 'required|date|after_or_equal:date_debut'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $abonnement->update([
                'statut' => $request->statut,
                'date_fin' => $request->date_fin
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function renouveler(Request $request, Abonnement $abonnement)
    {
        try {
            DB::beginTransaction();

            // Mettre l'ancien abonnement comme expiré
            $abonnement->update(['statut' => 'expiré']);

            // Calculer les nouvelles dates
            $dateDebut = \Carbon\Carbon::parse($abonnement->date_fin)->addDay();
            $dateFin = $dateDebut->copy();
            
            switch ($abonnement->type_abonnement) {
                case 'mensuel':
                    $dateFin->addMonth();
                    break;
                case 'trimestriel':
                    $dateFin->addMonths(3);
                    break;
                case 'annuel':
                    $dateFin->addYear();
                    break;
            }

            // Créer le nouvel abonnement
            $nouvelAbonnement = Abonnement::create([
                'abonne_id' => $abonnement->abonne_id,
                'activite_id' => $abonnement->activite_id,
                'type_abonnement' => $abonnement->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $abonnement->montant,
                'statut' => 'actif',
                'zk_sync' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelé avec succès',
                'abonnement' => $nouvelAbonnement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Abonnement $abonnement)
    {
        try {
            // Vérifier s'il y a des paiements
            $paiementsCount = $abonnement->paiements()->count();
            
            if ($paiementsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: abonnement a des paiements associés'
                ], 400);
            }

            $abonnement->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Abonnement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changerStatut(Request $request, Abonnement $abonnement)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,expiré,suspendu'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $abonnement->update(['statut' => $request->statut]);
            
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}