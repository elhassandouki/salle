<?php

namespace App\Http\Controllers;

use App\Models\AbonneAssurance;
use App\Models\Abonne;
use App\Models\AssuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AbonneAssuranceController extends Controller
{
    public function index(Request $request)
    {
        $totalAssurances = AbonneAssurance::count();
        $totalActives = AbonneAssurance::where('statut', 'actif')->count();
        $totalExpirees = AbonneAssurance::where('statut', 'expiré')->count();
        $totalResiliees = AbonneAssurance::where('statut', 'resilie')->count();
        
        $abonnes = Abonne::all();
        $companies = AssuranceCompany::all();

        return view('abonne_assurances.index', compact(
            'totalAssurances', 
            'totalActives', 
            'totalExpirees',
            'totalResiliees',
            'abonnes',
            'companies'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $abonneId = $request->input('filters.abonne_id');
        $companyId = $request->input('filters.company_id');

        $query = AbonneAssurance::with(['abonne', 'company']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('numero_contrat', 'LIKE', "%{$search}%")
                  ->orWhereHas('abonne', function($q2) use ($search) {
                      $q2->where('nom', 'LIKE', "%{$search}%")
                         ->orWhere('prenom', 'LIKE', "%{$search}%")
                         ->orWhere('cin', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('company', function($q2) use ($search) {
                      $q2->where('nom', 'LIKE', "%{$search}%");
                  });
            });
        }

        if (!empty($statut)) {
            $query->where('statut', $statut);
        }

        if (!empty($abonneId)) {
            $query->where('abonne_id', $abonneId);
        }

        if (!empty($companyId)) {
            $query->where('assurance_company_id', $companyId);
        }

        $totalRecords = $query->count();

        $assurances = $query->skip($start)
            ->take($length)
            ->orderBy('date_debut', 'desc')
            ->get();

        $data = [];
        foreach ($assurances as $index => $assurance) {
            $joursRestants = $assurance->jours_restants;
            $pourcentageUtilise = $assurance->pourcentage_utilise;
            
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $assurance->id,
                'abonne' => $assurance->abonne->nom . ' ' . $assurance->abonne->prenom,
                'company' => $assurance->company->nom,
                'contrat' => '
                    <div>
                        <strong>' . $assurance->numero_contrat . '</strong>
                        <div><small>Du ' . $assurance->date_debut->format('d/m/Y') . ' au ' . 
                               $assurance->date_fin->format('d/m/Y') . '</small></div>
                    </div>',
                'plafond' => '
                    <div class="text-center">
                        <div><small>Plafond:</small> ' . number_format($assurance->plafond_annuel, 2) . ' DH</div>
                        <div><small>Utilisé:</small> ' . number_format($assurance->montant_utilise, 2) . ' DH</div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar ' . ($pourcentageUtilise >= 80 ? 'bg-danger' : ($pourcentageUtilise >= 50 ? 'bg-warning' : 'bg-success')) . '" 
                                 style="width: ' . min(100, $pourcentageUtilise) . '%" title="' . round($pourcentageUtilise, 1) . '% utilisé">
                            </div>
                        </div>
                    </div>',
                'jours_restants' => $joursRestants > 0 ? 
                    '<span class="badge badge-' . ($joursRestants <= 30 ? 'warning' : 'success') . '">' . 
                    $joursRestants . ' jour(s)</span>' : 
                    '<span class="badge badge-secondary">Expiré</span>',
                'statut_badge' => '
                    <span class="badge badge-' . $assurance->statut_couleur . '">
                        ' . ucfirst($assurance->statut) . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $assurance->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $assurance->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-success reclamation-btn" data-id="' . $assurance->id . '" title="Réclamation">
                            <i class="fas fa-file-medical"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $assurance->id . '">
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
            'assurance_company_id' => 'required|exists:assurance_companies,id',
            'numero_contrat' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'plafond_annuel' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expiré,resilie',
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
            // Vérifier si l'abonné a déjà une assurance active avec cette compagnie
            $existing = AbonneAssurance::where('abonne_id', $request->abonne_id)
                ->where('assurance_company_id', $request->assurance_company_id)
                ->where('statut', 'actif')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'abonné a déjà une assurance active avec cette compagnie'
                ], 400);
            }

            $assurance = AbonneAssurance::create([
                'abonne_id' => $request->abonne_id,
                'assurance_company_id' => $request->assurance_company_id,
                'numero_contrat' => $request->numero_contrat,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'plafond_annuel' => $request->plafond_annuel,
                'montant_utilise' => 0,
                'statut' => $request->statut,
                'notes' => $request->notes
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Assurance créée avec succès',
                'assurance' => $assurance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, AbonneAssurance $abonneAssurance)
    {
        $validator = Validator::make($request->all(), [
            'numero_contrat' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'plafond_annuel' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expiré,resilie',
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
            $abonneAssurance->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Assurance mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changerStatut(Request $request, AbonneAssurance $abonneAssurance)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,expiré,resilie'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $abonneAssurance->update(['statut' => $request->statut]);
            
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

    public function destroy(AbonneAssurance $abonneAssurance)
    {
        try {
            // Vérifier s'il y a des réclamations
            $reclamationsCount = $abonneAssurance->reclamations()->count();
            
            if ($reclamationsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: assurance a des réclamations associées'
                ], 400);
            }

            $abonneAssurance->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Assurance supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function renouveler(Request $request, AbonneAssurance $abonneAssurance)
    {
        $validator = Validator::make($request->all(), [
            'date_fin' => 'required|date|after_or_equal:today',
            'plafond_annuel' => 'required|numeric|min:0'
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

            // Mettre l'ancienne assurance comme expirée
            $abonneAssurance->update(['statut' => 'expiré']);

            // Créer la nouvelle assurance
            $nouvelleAssurance = AbonneAssurance::create([
                'abonne_id' => $abonneAssurance->abonne_id,
                'assurance_company_id' => $abonneAssurance->assurance_company_id,
                'numero_contrat' => $request->numero_contrat ?? 'REN-' . $abonneAssurance->numero_contrat,
                'date_debut' => now(),
                'date_fin' => $request->date_fin,
                'plafond_annuel' => $request->plafond_annuel,
                'montant_utilise' => 0,
                'statut' => 'actif',
                'notes' => 'Renouvellement de l\'assurance ' . $abonneAssurance->numero_contrat
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assurance renouvelée avec succès',
                'assurance' => $nouvelleAssurance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = AbonneAssurance::with(['abonne', 'company']);
        
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->has('abonne_id') && $request->abonne_id) {
            $query->where('abonne_id', $request->abonne_id);
        }
        
        if ($request->has('company_id') && $request->company_id) {
            $query->where('assurance_company_id', $request->company_id);
        }
        
        $assurances = $query->orderBy('date_debut', 'desc')->get();
        
        $fileName = 'abonne_assurances_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($assurances) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Abonné',
                'Compagnie',
                'N° Contrat',
                'Date début',
                'Date fin',
                'Plafond annuel (DH)',
                'Montant utilisé (DH)',
                'Solde (DH)',
                'Statut',
                'Notes'
            ], ';');
            
            // Données
            foreach ($assurances as $assurance) {
                fputcsv($file, [
                    $assurance->id,
                    $assurance->abonne->nom . ' ' . $assurance->abonne->prenom,
                    $assurance->company->nom,
                    $assurance->numero_contrat,
                    $assurance->date_debut->format('d/m/Y'),
                    $assurance->date_fin->format('d/m/Y'),
                    $assurance->plafond_annuel,
                    $assurance->montant_utilise,
                    $assurance->solde,
                    ucfirst($assurance->statut),
                    $assurance->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}