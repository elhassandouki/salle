<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\Abonnement;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Pointage;
use App\Services\ZKTecoService;

class AbonneController extends Controller
{
    /**
     * Afficher la liste des abonnés
     */
    public function index(Request $request)
    {
        // Statistiques
        $totalAbonnes = Abonne::count();
        $totalHommes = Abonne::where('sexe', 'Homme')->orWhere('sexe', 'homme')->orWhere('sexe', 'H')->orWhere('sexe', 'h')->count();
        $totalFemmes = Abonne::where('sexe', 'Femme')->orWhere('sexe', 'femme')->orWhere('sexe', 'F')->orWhere('sexe', 'f')->count();
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
 * Get data for DataTable - Version corrigée avec le bon format
 */
public function getData(Request $request)
{
    $query = Abonne::with('abonnements');

    // Filtre recherche
    if ($request->has('filters.search') && !empty($request->filters['search'])) {
        $search = $request->filters['search'];
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
    if ($request->has('filters.sexe') && !empty($request->filters['sexe'])) {
        $sexe = $request->filters['sexe'];
        if ($sexe === 'Homme') {
            $query->where(function($q) {
                $q->where('sexe', 'Homme')
                  ->orWhere('sexe', 'homme')
                  ->orWhere('sexe', 'H')
                  ->orWhere('sexe', 'h')
                  ->orWhere('sexe', 'M')
                  ->orWhere('sexe', 'm');
            });
        } elseif ($sexe === 'Femme') {
            $query->where(function($q) {
                $q->where('sexe', 'Femme')
                  ->orWhere('sexe', 'femme')
                  ->orWhere('sexe', 'F')
                  ->orWhere('sexe', 'f')
                  ->orWhere('sexe', 'W')
                  ->orWhere('sexe', 'w');
            });
        }
    }

    // Filtre statut
    if ($request->has('filters.statut') && !empty($request->filters['statut'])) {
        if ($request->filters['statut'] === 'actif') {
            $query->actifs();
        } elseif ($request->filters['statut'] === 'inactif') {
            $query->inactifs();
        } elseif ($request->filters['statut'] === 'expire_bientot') {
            $query->expireBientot(7);
        }
    }

    // Filtre type abonnement
    if ($request->has('filters.type_abonnement') && !empty($request->filters['type_abonnement'])) {
        $type = $request->filters['type_abonnement'];
        $query->whereHas('abonnements', function($q) use ($type) {
            $q->where('type_abonnement', $type)
              ->where('statut', 'actif');
        });
    }

    // Pagination
    $totalRecords = $query->count();
    $start = $request->input('start', 0);
    $length = $request->input('length', 10);
    
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
            'type_abonnement' => $abonnementActif ? ucfirst($abonnementActif->type_abonnement) : '-',
            'expiration_badge' => $expirationBadge,
            'action' => $action
        ];
    }

    // Retourner au format DataTables
    return response()->json([
        'draw' => intval($request->input('draw', 0)),
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
     * Synchroniser un seul abonné vers ZKTeco.
     */
    public function syncZKTeco(Abonne $abonne, ZKTecoService $zkService)
    {
        if (! $abonne->est_actif) {
            return response()->json([
                'success' => false,
                'message' => 'Cet abonné n\'a pas d\'abonnement actif.',
            ], 422);
        }

        $payload = [$this->prepareZkUserPayload($abonne)];
        $result = $zkService->syncUsersToDevice($payload);
        $this->logZkSyncAttempt('sync_zkteco_single', 'Abonne', $abonne->id, [
            'scope' => 'single',
            'abonne_id' => $abonne->id,
            'result' => $result,
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Abonné synchronisé avec ZKTeco.'
                : ($result['errors'][0] ?? 'La synchronisation ZKTeco a échoué.'),
            'data' => $result,
        ]);
    }

    /**
     * Synchroniser plusieurs abonnés vers ZKTeco selon le filtre choisi.
     */
    public function syncAllZKTeco(Request $request, ZKTecoService $zkService)
    {
        $validator = Validator::make($request->all(), [
            'sync_scope' => 'required|in:actifs,all',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Choix de synchronisation invalide.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Abonne::query()->orderBy('nom')->orderBy('prenom');

        if ($request->sync_scope === 'actifs') {
            $query->actifs();
        }

        $abonnes = $query->get();

        if ($abonnes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => $request->sync_scope === 'actifs'
                    ? 'Aucun abonné actif à synchroniser.'
                    : 'Aucun abonné trouvé pour la synchronisation.',
            ], 422);
        }

        $users = $abonnes->map(function (Abonne $abonne) {
            return $this->prepareZkUserPayload($abonne);
        })->values()->all();

        $result = $zkService->syncUsersToDevice($users);
        $this->logZkSyncAttempt('sync_zkteco_bulk', 'Abonne', null, [
            'scope' => $request->sync_scope,
            'abonne_ids' => $abonnes->pluck('id')->all(),
            'requested_total' => $abonnes->count(),
            'result' => $result,
        ]);

        $scopeLabel = $request->sync_scope === 'actifs' ? 'abonnés actifs' : 'tous les abonnés';
        $message = $result['success']
            ? "{$result['synced_count']} {$scopeLabel} synchronisés vers ZKTeco."
            : ($result['errors'][0] ?? 'Machine ZKTeco non disponible pour le moment. La tentative a été enregistrée.');

        return response()->json([
            'success' => $result['success'],
            'message' => $message,
            'data' => $result,
        ]);
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

    public function getAbonnesForZKTeco(Request $request)
    {
        $scope = $request->input('sync_scope', 'actifs');
        $query = Abonne::query()->orderBy('nom')->orderBy('prenom');

        if ($scope === 'actifs') {
            $query->actifs();
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(function (Abonne $abonne) {
                return [
                    'id' => $abonne->id,
                    'uid' => $abonne->uid ?: (string) $abonne->id,
                    'nom_complet' => $abonne->nom_complet,
                    'card_id' => $abonne->card_id,
                    'est_actif' => $abonne->est_actif,
                ];
            }),
        ]);
    }

    public function checkZkStatus()
    {
        $lastPointage = Pointage::latest('date_pointage')->first();

        return view('zkteco.status', [
            'deviceName' => 'ZKTeco F18',
            'status' => 'Configuration manuelle',
            'lastSync' => optional($lastPointage?->date_pointage)->format('d/m/Y H:i'),
            'todayEntries' => Pointage::whereDate('date_pointage', today())->count(),
            'totalAbonnes' => Abonne::count(),
            'syncEndpoint' => route('api.zkteco.sync'),
        ]);
    }

    private function prepareZkUserPayload(Abonne $abonne): array
    {
        $uid = $abonne->uid ?: (string) $abonne->id;

        if (! $abonne->uid) {
            $abonne->forceFill(['uid' => $uid])->save();
        }

        return [
            'uid' => $uid,
            'user_id' => $uid,
            'name' => trim($abonne->nom . ' ' . $abonne->prenom),
            'cardno' => $abonne->card_id ?: '',
        ];
    }

    private function logZkSyncAttempt(string $action, ?string $model, ?int $modelId, array $payload): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'new_data' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Impossible d\'enregistrer le log ZKTeco: ' . $e->getMessage());
        }
    }
}
