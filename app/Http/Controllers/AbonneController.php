<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AbonneController extends Controller
{
    /**
     * Afficher la liste des abonnés
     */
    public function index(Request $request)
    {
        // Statistiques
        $totalAbonnes = Abonne::count();
        $totalHommes = Abonne::where('sexe', 'Homme')->count();
        $totalFemmes = Abonne::where('sexe', 'Femme')->count();
        $totalActifs = Abonne::actifs()->count();
        $totalInactifs = $totalAbonnes - $totalActifs;
        $totalExpireBientot = Abonne::expireBientot(7)->count();

        return view('abonnes.index', compact(
            'totalAbonnes', 
            'totalHommes', 
            'totalFemmes',
            'totalActifs',
            'totalInactifs',
            'totalExpireBientot'
        ));
    }

    /**
     * Get data for DataTable
     */
    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('filters.search');
        $sexe = $request->input('filters.sexe');
        $statut = $request->input('filters.statut');

        $query = Abonne::with('abonnements');

        // Filtre recherche
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('cin', 'LIKE', "%{$search}%")
                  ->orWhere('card_id', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Filtre sexe
        if (!empty($sexe)) {
            $query->where('sexe', $sexe);
        }

        // Filtre statut
        if ($statut === 'actif') {
            $query->actifs();
        } elseif ($statut === 'inactif') {
            $query->inactifs();
        } elseif ($statut === 'expire_bientot') {
            $query->expireBientot(7);
        }

        $totalRecords = $query->count();

        $abonnes = $query->orderBy('created_at', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($abonnes as $index => $abonne) {
            // Mettre à jour les statuts des abonnements
            foreach ($abonne->abonnements as $abonnement) {
                $abonnement->updateStatut();
            }

            // Récupérer l'abonnement actif
            $abonnementActif = $abonne->abonnement_actif;
            
            // Badge statut
            if ($abonne->est_actif) {
                $statutBadge = '<span class="badge badge-success">Actif</span>';
            } else {
                $statutBadge = '<span class="badge badge-secondary">Inactif</span>';
            }

            // Badge expiration
            if ($abonnementActif) {
                $joursRestants = $abonnementActif->jours_restants;
                if ($joursRestants <= 3) {
                    $expirationBadge = '<span class="badge badge-danger">Expire dans ' . $joursRestants . 'j</span>';
                } elseif ($joursRestants <= 7) {
                    $expirationBadge = '<span class="badge badge-warning">Expire dans ' . $joursRestants . 'j</span>';
                } else {
                    $expirationBadge = '<span class="badge badge-info">' . $joursRestants . 'j restants</span>';
                }
            } else {
                $expirationBadge = '<span class="badge badge-secondary">Pas d\'abonnement</span>';
            }

            // Photo
            $photo = $abonne->photo 
                ? '<img src="/storage/'.$abonne->photo.'" class="img-circle elevation-2" style="width: 40px; height: 40px; object-fit: cover;">'
                : '<img src="https://ui-avatars.com/api/?name='.urlencode($abonne->nom.'+'.$abonne->prenom).'&background=0D6EFD&color=fff&size=40" class="img-circle elevation-2" style="width: 40px; height: 40px;">';

            // Actions
            $action = '
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-info view-btn" data-id="'.$abonne->id.'" title="Voir">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-warning edit-btn" data-id="'.$abonne->id.'" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-success abonnement-btn" data-id="'.$abonne->id.'" title="Gérer abonnement">
                    <i class="fas fa-calendar-alt"></i>
                </button>
                <button type="button" class="btn btn-danger delete-btn" data-id="'.$abonne->id.'" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>';

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $abonne->id,
                'photo' => $photo,
                'nom_complet' => $abonne->nom_complet,
                'cin' => $abonne->cin ?? '-',
                'card_id' => $abonne->card_id ?? '-',
                'telephone' => $abonne->telephone,
                'email' => $abonne->email ?? '-',
                'sexe' => $abonne->sexe ?? '-',
                'date_naissance' => $abonne->date_naissance ? $abonne->date_naissance->format('d/m/Y') : '-',
                'age' => $abonne->age ?? '-',
                'statut_badge' => $statutBadge,
                'expiration_badge' => $expirationBadge,
                'type_abonnement' => $abonnementActif ? ucfirst($abonnementActif->type_abonnement) : '-',
                'date_fin' => $abonne->date_fin_abonnement ?? '-',
                'action' => $action
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'cin' => 'nullable|string|max:20|unique:abonnes,cin',
            'card_id' => 'nullable|string|max:50|unique:abonnes,card_id',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100|unique:abonnes,email',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'sexe' => 'nullable|in:Homme,Femme',
            'nationalite' => 'nullable|string|max:50',
            'situation_familiale' => 'nullable|string|max:20',
            'profession' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except('photo');
            
            // Gérer la photo
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                try {
                    $path = $request->file('photo')->store('abonnes/photos', 'public');
                    $data['photo'] = $path;
                } catch (\Exception $e) {
                    \Log::warning('Photo upload failed: ' . $e->getMessage());
                }
            }

            $abonne = Abonne::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonné ajouté avec succès',
                'data' => $abonne
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'enregistrement: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Abonne $abonne)
    {
        // Mettre à jour les statuts des abonnements
        foreach ($abonne->abonnements as $abonnement) {
            $abonnement->updateStatut();
        }

        // Charger les abonnements
        $abonne->load('abonnements');

        return response()->json([
            'success' => true,
            'data' => [
                'abonne' => $abonne,
                'abonnements' => $abonne->abonnements,
                'est_actif' => $abonne->est_actif,
                'age' => $abonne->age,
                'date_fin' => $abonne->date_fin_abonnement,
                'type_abonnement' => $abonne->type_abonnement
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Abonne $abonne)
    {
        return response()->json([
            'success' => true,
            'abonne' => $abonne
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Abonne $abonne)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'cin' => 'nullable|string|max:20|unique:abonnes,cin,' . $abonne->id,
            'card_id' => 'nullable|string|max:50|unique:abonnes,card_id,' . $abonne->id,
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100|unique:abonnes,email,' . $abonne->id,
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:100',
            'sexe' => 'nullable|in:Homme,Femme',
            'nationalite' => 'nullable|string|max:50',
            'situation_familiale' => 'nullable|string|max:20',
            'profession' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->except('photo');
            
            // Gérer la photo
            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo
                if ($abonne->photo) {
                    Storage::disk('public')->delete($abonne->photo);
                }
                
                try {
                    $path = $request->file('photo')->store('abonnes/photos', 'public');
                    $data['photo'] = $path;
                } catch (\Exception $e) {
                    \Log::warning('Photo upload failed: ' . $e->getMessage());
                }
            }

            $abonne->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonné mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Abonne $abonne)
    {
        try {
            DB::beginTransaction();

            // Supprimer la photo
            if ($abonne->photo) {
                Storage::disk('public')->delete($abonne->photo);
            }

            // Supprimer les abonnements associés
            $abonne->abonnements()->delete();

            $abonne->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonné supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter un abonnement à un abonné
     */
    public function ajouterAbonnement(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type_abonnement' => 'required|in:mensuel,trimestriel,annuel',
            'date_debut' => 'required|date',
            'montant' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $abonne = Abonne::findOrFail($id);
            
            // Calculer la date de fin selon le type
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin = clone $dateDebut;
            
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

            // Désactiver les anciens abonnements
            $abonne->abonnements()->update(['statut' => 'expiré']);

            // Créer le nouvel abonnement
            $abonnement = $abonne->abonnements()->create([
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $request->montant,
                'statut' => 'actif',
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Abonnement ajouté avec succès',
                'data' => $abonnement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un abonné
     */
    public function checkStatut($id)
    {
        $abonne = Abonne::with('abonnements')->findOrFail($id);
        
        // Mettre à jour les statuts des abonnements expirés
        foreach ($abonne->abonnements as $abonnement) {
            $abonnement->updateStatut();
        }

        $abonne->refresh();

        return response()->json([
            'success' => true,
            'est_actif' => $abonne->est_actif,
            'date_fin' => $abonne->date_fin_abonnement,
            'type_abonnement' => $abonne->type_abonnement,
            'abonnement_actif' => $abonne->abonnement_actif
        ]);
    }

    /**
     * Export des abonnés en CSV
     */
    public function export(Request $request)
    {
        $query = Abonne::with('abonnements');
        
        // Appliquer les filtres
        if ($request->has('statut') && $request->statut !== '') {
            if ($request->statut === 'actif') {
                $query->actifs();
            } elseif ($request->statut === 'inactif') {
                $query->inactifs();
            }
        }
        
        $abonnes = $query->orderBy('nom')->orderBy('prenom')->get();
        
        $fileName = 'abonnes_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($abonnes) {
            $file = fopen('php://output', 'w');
            
            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Nom',
                'Prénom',
                'CIN',
                'Carte N°',
                'Téléphone',
                'Email',
                'Sexe',
                'Date Naissance',
                'Âge',
                'Statut',
                'Type Abonnement',
                'Date Fin Abonnement',
                'Adresse',
                'Notes'
            ], ';');
            
            // Données
            foreach ($abonnes as $abonne) {
                fputcsv($file, [
                    $abonne->id,
                    $abonne->nom,
                    $abonne->prenom,
                    $abonne->cin ?? '',
                    $abonne->card_id ?? '',
                    $abonne->telephone,
                    $abonne->email ?? '',
                    $abonne->sexe ?? '',
                    $abonne->date_naissance ? $abonne->date_naissance->format('d/m/Y') : '',
                    $abonne->age ?? '',
                    $abonne->est_actif ? 'Actif' : 'Inactif',
                    $abonne->type_abonnement ?? '',
                    $abonne->date_fin_abonnement ?? '',
                    $abonne->adresse ?? '',
                    $abonne->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}