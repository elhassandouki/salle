<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\ActivityLog;
use App\Models\Pointage;
use App\Services\ZKTecoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
     * Importer directement les utilisateurs existants sur la machine ZKTeco.
     */
    public function importZkUsers(ZKTecoService $zkService)
    {
        $importTrace = ['import_request_received'];
        $deviceResult = $zkService->getUsersFromDeviceDetailed();
        $importTrace = array_merge($importTrace, $deviceResult['trace'] ?? []);

        if (! ($deviceResult['success'] ?? false)) {
            $this->logZkSyncAttempt('import_zkteco_users', 'Abonne', null, [
                'current_step' => $deviceResult['step'] ?? 'device_read_failed',
                'trace' => $importTrace,
                'result' => $deviceResult,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import ZKTeco interrompu a l etape: ' . ($deviceResult['step'] ?? 'device_read_failed') . '. ' . ($deviceResult['message'] ?? ''),
                'data' => [
                    'current_step' => $deviceResult['step'] ?? 'device_read_failed',
                    'trace' => $importTrace,
                ],
            ], 422);
        }

        $deviceUsers = $deviceResult['users'] ?? [];

        if ($deviceUsers === []) {
            $importTrace[] = 'no_users_found';
            $this->logZkSyncAttempt('import_zkteco_users', 'Abonne', null, [
                'current_step' => 'no_users_found',
                'trace' => $importTrace,
                'result' => $deviceResult,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import ZKTeco arrete: aucun utilisateur trouve sur la machine.',
                'data' => [
                    'current_step' => 'no_users_found',
                    'trace' => $importTrace,
                ],
            ], 422);
        }

        $imported = 0;
        $skipped = 0;
        $importTrace[] = 'database_transaction_started';

        DB::beginTransaction();

        try {
            foreach ($deviceUsers as $deviceUser) {
                $importTrace[] = 'processing_device_user';
                $uid = $this->sanitizeZkValue($deviceUser['uid'] ?? $deviceUser['userid'] ?? null);
                $cardId = $this->sanitizeZkValue($deviceUser['cardno'] ?? $deviceUser['card_id'] ?? null);
                $fullName = $this->sanitizeZkValue($deviceUser['name'] ?? null) ?: 'Utilisateur ZK';

                if ($uid === null && $cardId === null) {
                    $importTrace[] = 'user_skipped_missing_identifiers';
                    $skipped++;
                    continue;
                }

                $alreadyExists = Abonne::query()
                    ->when($uid !== null, fn ($query) => $query->orWhere('uid', $uid))
                    ->when($cardId !== null, fn ($query) => $query->orWhere('card_id', $cardId))
                    ->exists();

                if ($alreadyExists) {
                    $importTrace[] = 'user_skipped_duplicate';
                    $skipped++;
                    continue;
                }

                [$nom, $prenom] = $this->splitImportedFullName($fullName);
                $importTrace[] = 'sql_insert_started';

                Abonne::create([
                    'uid' => $uid,
                    'card_id' => $cardId,
                    'nom' => $nom,
                    'prenom' => $prenom,
                ]);

                $importTrace[] = 'sql_insert_ok';
                $imported++;
            }

            DB::commit();
            $importTrace[] = 'database_commit_ok';
            $this->logZkSyncAttempt('import_zkteco_users', 'Abonne', null, [
                'current_step' => 'database_commit_ok',
                'trace' => $importTrace,
                'summary' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total' => count($deviceUsers),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$imported} abonnes importes depuis la machine.",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total' => count($deviceUsers),
                    'current_step' => 'database_commit_ok',
                    'trace' => $importTrace,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $importTrace[] = 'database_rollback';
            $this->logZkSyncAttempt('import_zkteco_users', 'Abonne', null, [
                'current_step' => 'database_rollback',
                'trace' => $importTrace,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'import depuis ZKTeco a l etape database_rollback: " . $e->getMessage(),
                'data' => [
                    'current_step' => 'database_rollback',
                    'trace' => $importTrace,
                ],
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

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier import invalide.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');

        try {
            $rows = $this->parseImportFile($file->getRealPath());

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier est vide ou ne contient pas de donnees exploitables.',
                ], 422);
            }

            $header = array_shift($rows);
            $normalizedHeader = array_map([$this, 'normalizeImportHeader'], $header);
            $imported = 0;
            $updated = 0;
            $skipped = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $mappedRow = $this->mapImportRow($normalizedHeader, $row);
                $rowNumber = $index + 2;
                $preparedRow = $this->prepareImportRow($mappedRow);

                if (empty($preparedRow['nom']) || empty($preparedRow['prenom'])) {
                    $skipped[] = "Ligne {$rowNumber}: nom ou prenom manquant.";
                    continue;
                }

                if (
                    empty($preparedRow['telephone']) &&
                    empty($preparedRow['cin']) &&
                    empty($preparedRow['email']) &&
                    empty($preparedRow['card_id'])
                ) {
                    $skipped[] = "Ligne {$rowNumber}: aucun identifiant exploitable.";
                    continue;
                }

                $abonne = $this->findExistingAbonneForImport($preparedRow);

                if ($abonne) {
                    $abonne->fill($preparedRow)->save();
                    $updated++;
                    continue;
                }

                Abonne::create($preparedRow);
                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import termine avec succes.',
                'data' => [
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'import: " . $e->getMessage(),
            ], 500);
        }
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

    private function parseImportFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false || $lines === []) {
            return [];
        }

        $delimiter = $this->detectImportDelimiter($lines[0]);
        $rows = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $rows[] = str_getcsv($trimmed, $delimiter);
        }

        return $rows;
    }

    private function detectImportDelimiter(string $line): string
    {
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private function normalizeImportHeader(string $header): string
    {
        $normalized = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $header)));
        $normalized = str_replace(
            ['"', "'", ' ', '-', '.', '(', ')', '/', '\\'],
            '',
            $normalized
        );

        return match ($normalized) {
            'fullname', 'nomcomplet' => 'nom_complet',
            'firstname', 'prenom' => 'prenom',
            'lastname', 'nom' => 'nom',
            'tele', 'telephone', 'phone', 'mobile' => 'telephone',
            'cardid', 'carte', 'carten', 'cartenumero' => 'card_id',
            'datenaissance', 'naissance', 'birthdate' => 'date_naissance',
            default => $normalized,
        };
    }

    private function mapImportRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $mapped[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $mapped;
    }

    private function prepareImportRow(array $row): array
    {
        if (! empty($row['nom_complet']) && (empty($row['nom']) || empty($row['prenom']))) {
            $parts = preg_split('/\s+/', trim((string) $row['nom_complet']));
            $row['nom'] = $row['nom'] ?? array_shift($parts);
            $row['prenom'] = $row['prenom'] ?? implode(' ', $parts);
        }

        $data = array_filter([
            'nom' => $row['nom'] ?? null,
            'prenom' => $row['prenom'] ?? null,
            'cin' => $row['cin'] ?? null,
            'card_id' => $row['card_id'] ?? null,
            'telephone' => $row['telephone'] ?? null,
            'email' => $row['email'] ?? null,
            'sexe' => $this->normalizeGender($row['sexe'] ?? null),
            'date_naissance' => $this->normalizeImportDate($row['date_naissance'] ?? null),
            'lieu_naissance' => $row['lieu_naissance'] ?? null,
            'nationalite' => $row['nationalite'] ?? null,
            'situation_familiale' => $row['situation_familiale'] ?? null,
            'profession' => $row['profession'] ?? null,
            'adresse' => $row['adresse'] ?? null,
            'notes' => $row['notes'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        return $data;
    }

    private function normalizeGender(?string $gender): ?string
    {
        if ($gender === null || trim($gender) === '') {
            return null;
        }

        return match (strtolower(trim($gender))) {
            'homme', 'h', 'm', 'male' => 'Homme',
            'femme', 'f', 'w', 'female' => 'Femme',
            default => null,
        };
    }

    private function normalizeImportDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function findExistingAbonneForImport(array $data): ?Abonne
    {
        return Abonne::query()
            ->when(! empty($data['cin']), fn ($query) => $query->orWhere('cin', $data['cin']))
            ->when(! empty($data['email']), fn ($query) => $query->orWhere('email', $data['email']))
            ->when(! empty($data['card_id']), fn ($query) => $query->orWhere('card_id', $data['card_id']))
            ->when(! empty($data['telephone']), fn ($query) => $query->orWhere('telephone', $data['telephone']))
            ->first();
    }

    private function sanitizeZkValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '' || $stringValue === '0') {
            return null;
        }

        return $stringValue;
    }

    private function splitImportedFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if ($fullName === '') {
            return ['Utilisateur', 'ZK'];
        }

        $parts = explode(' ', $fullName);
        $nom = array_shift($parts) ?: 'Utilisateur';
        $prenom = trim(implode(' ', $parts));

        return [$nom, $prenom !== '' ? $prenom : 'ZK'];
    }
}
