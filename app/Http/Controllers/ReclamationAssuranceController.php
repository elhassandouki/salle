<?php

namespace App\Http\Controllers;

use App\Models\ReclamationAssurance;
use App\Models\AbonneAssurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ReclamationAssuranceController extends Controller
{
    public function index(Request $request)
    {
        $totalReclamations = ReclamationAssurance::count();
        $enAttente = ReclamationAssurance::where('statut', 'en_attente')->count();
        $approuvees = ReclamationAssurance::where('statut', 'approuve')->count();
        $remboursees = ReclamationAssurance::where('statut', 'rembourse')->count();
        $refusees = ReclamationAssurance::where('statut', 'refuse')->count();
        
        $totalMontant = ReclamationAssurance::sum('montant_total');
        $totalRemboursable = ReclamationAssurance::sum('montant_remboursable');
        
        $assurances = AbonneAssurance::with(['abonne', 'company'])
            ->where('statut', 'actif')
            ->get();

        return view('reclamation_assurances.index', compact(
            'totalReclamations', 
            'enAttente', 
            'approuvees',
            'remboursees',
            'refusees',
            'totalMontant',
            'totalRemboursable',
            'assurances'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $type = $request->input('filters.type');
        $dateDebut = $request->input('filters.date_debut');
        $dateFin = $request->input('filters.date_fin');
        $assuranceId = $request->input('filters.assurance_id');

        $query = ReclamationAssurance::with(['abonneAssurance.abonne', 'abonneAssurance.company']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('abonneAssurance.abonne', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%")
                       ->orWhere('prenom', 'LIKE', "%{$search}%")
                       ->orWhere('cin', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('abonneAssurance.company', function($q2) use ($search) {
                    $q2->where('nom', 'LIKE', "%{$search}%");
                })
                ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($statut)) {
            $query->where('statut', $statut);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($dateDebut)) {
            $query->where('date_reclamation', '>=', $dateDebut);
        }

        if (!empty($dateFin)) {
            $query->where('date_reclamation', '<=', $dateFin);
        }

        if (!empty($assuranceId)) {
            $query->where('abonne_assurance_id', $assuranceId);
        }

        $totalRecords = $query->count();

        $reclamations = $query->skip($start)
            ->take($length)
            ->orderBy('date_reclamation', 'desc')
            ->get();

        $data = [];
        foreach ($reclamations as $index => $reclamation) {
            $pourcentage = $reclamation->pourcentage_remboursement;
            
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $reclamation->id,
                'abonne' => $reclamation->abonneAssurance->abonne->nom . ' ' . 
                           $reclamation->abonneAssurance->abonne->prenom,
                'company' => $reclamation->abonneAssurance->company->nom,
                'type' => '
                    <span class="badge badge-info">' . $reclamation->type_text . '</span>',
                'montants' => '
                    <div class="text-right">
                        <div><small>Total:</small> ' . number_format($reclamation->montant_total, 2) . ' DH</div>
                        <div><small>Remboursable:</small> ' . number_format($reclamation->montant_remboursable, 2) . ' DH</div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" 
                                 style="width: ' . $pourcentage . '%" title="' . round($pourcentage, 1) . '%">
                            </div>
                        </div>
                    </div>',
                'dates' => '
                    <div class="text-center">
                        <div><small>Réclamation:</small> ' . $reclamation->date_reclamation->format('d/m/Y') . '</div>
                        ' . ($reclamation->date_traitement ? 
                            '<div><small>Traitement:</small> ' . $reclamation->date_traitement->format('d/m/Y') . '</div>' : 
                            '') . '
                    </div>',
                'statut_badge' => '
                    <span class="badge badge-' . $reclamation->statut_couleur . '">
                        ' . $reclamation->statut_text . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $reclamation->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $reclamation->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-success traiter-btn" data-id="' . $reclamation->id . '" title="Traiter">
                            <i class="fas fa-check"></i>
                        </button>
                        ' . ($reclamation->justificatif_path ? 
                            '<a href="' . $reclamation->justificatif_url . '" class="btn btn-primary" target="_blank" title="Justificatif">
                                <i class="fas fa-file"></i>
                            </a>' : '') . '
                        <button class="btn btn-danger delete-btn" data-id="' . $reclamation->id . '">
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
            'abonne_assurance_id' => 'required|exists:abonne_assurances,id',
            'type' => 'required|in:consultation,examen,medicament,rehabilitation',
            'montant_total' => 'required|numeric|min:0',
            'date_reclamation' => 'required|date',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            DB::beginTransaction();

            $assurance = AbonneAssurance::findOrFail($request->abonne_assurance_id);
            
            // Calculer le montant remboursable selon le taux de couverture
            $tauxCouverture = $assurance->company->taux_couverture;
            $montantRemboursable = ($request->montant_total * $tauxCouverture) / 100;
            
            // Vérifier si le plafond est dépassé
            $nouveauTotal = $assurance->montant_utilise + $montantRemboursable;
            if ($nouveauTotal > $assurance->plafond_annuel) {
                // Ajuster le montant remboursable pour ne pas dépasser le plafond
                $montantRemboursable = max(0, $assurance->plafond_annuel - $assurance->montant_utilise);
            }

            $data = [
                'abonne_assurance_id' => $request->abonne_assurance_id,
                'type' => $request->type,
                'montant_total' => $request->montant_total,
                'montant_remboursable' => $montantRemboursable,
                'date_reclamation' => $request->date_reclamation,
                'statut' => 'en_attente',
                'notes' => $request->notes
            ];

            if ($request->hasFile('justificatif')) {
                $path = $request->file('justificatif')->store('reclamations/justificatifs', 'public');
                $data['justificatif_path'] = $path;
            }

            $reclamation = ReclamationAssurance::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation créée avec succès',
                'reclamation' => $reclamation
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ReclamationAssurance $reclamationAssurance)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:consultation,examen,medicament,rehabilitation',
            'montant_total' => 'required|numeric|min:0',
            'montant_remboursable' => 'required|numeric|min:0',
            'date_reclamation' => 'required|date',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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
            $data = $request->except('justificatif');
            
            if ($request->hasFile('justificatif')) {
                // Supprimer l'ancien justificatif
                if ($reclamationAssurance->justificatif_path) {
                    Storage::disk('public')->delete($reclamationAssurance->justificatif_path);
                }
                
                $path = $request->file('justificatif')->store('reclamations/justificatifs', 'public');
                $data['justificatif_path'] = $path;
            }

            $reclamationAssurance->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Réclamation mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function traiter(Request $request, ReclamationAssurance $reclamationAssurance)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:approuve,refuse,rembourse',
            'date_traitement' => 'required|date',
            'notes_traitement' => 'nullable|string'
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

            $reclamationAssurance->update([
                'statut' => $request->statut,
                'date_traitement' => $request->date_traitement,
                'notes' => $reclamationAssurance->notes . "\n\n--- Traitement ---\n" . 
                          'Statut: ' . $request->statut . "\n" .
                          'Date: ' . $request->date_traitement . "\n" .
                          'Notes: ' . ($request->notes_traitement ?? '')
            ]);

            // Si la réclamation est approuvée, mettre à jour le montant utilisé de l'assurance
            if ($request->statut === 'approuve' || $request->statut === 'rembourse') {
                $assurance = $reclamationAssurance->abonneAssurance;
                $assurance->updateMontantUtilise();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation traitée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ReclamationAssurance $reclamationAssurance)
    {
        try {
            // Supprimer le justificatif si existe
            if ($reclamationAssurance->justificatif_path) {
                Storage::disk('public')->delete($reclamationAssurance->justificatif_path);
            }

            // Si la réclamation était approuvée, ajuster le montant utilisé
            if ($reclamationAssurance->statut === 'approuve' || $reclamationAssurance->statut === 'rembourse') {
                $assurance = $reclamationAssurance->abonneAssurance;
                $assurance->update(['montant_utilise' => $assurance->montant_utilise - $reclamationAssurance->montant_remboursable]);
            }

            $reclamationAssurance->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Réclamation supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = ReclamationAssurance::with(['abonneAssurance.abonne', 'abonneAssurance.company']);
        
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('date_debut') && $request->date_debut) {
            $query->where('date_reclamation', '>=', $request->date_debut);
        }
        
        if ($request->has('date_fin') && $request->date_fin) {
            $query->where('date_reclamation', '<=', $request->date_fin);
        }
        
        if ($request->has('assurance_id') && $request->assurance_id) {
            $query->where('abonne_assurance_id', $request->assurance_id);
        }
        
        $reclamations = $query->orderBy('date_reclamation', 'desc')->get();
        
        $fileName = 'reclamations_assurance_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($reclamations) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Date réclamation',
                'Abonné',
                'Compagnie',
                'Type',
                'Montant total (DH)',
                'Montant remboursable (DH)',
                'Pourcentage',
                'Statut',
                'Date traitement',
                'Délai traitement (jours)',
                'Notes'
            ], ';');
            
            // Données
            foreach ($reclamations as $reclamation) {
                fputcsv($file, [
                    $reclamation->id,
                    $reclamation->date_reclamation->format('d/m/Y'),
                    $reclamation->abonneAssurance->abonne->nom . ' ' . $reclamation->abonneAssurance->abonne->prenom,
                    $reclamation->abonneAssurance->company->nom,
                    $reclamation->type_text,
                    $reclamation->montant_total,
                    $reclamation->montant_remboursable,
                    round($reclamation->pourcentage_remboursement, 2) . '%',
                    $reclamation->statut_text,
                    $reclamation->date_traitement ? $reclamation->date_traitement->format('d/m/Y') : '',
                    $reclamation->delai_traitement ?? '',
                    $reclamation->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}