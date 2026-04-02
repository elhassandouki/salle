<?php

namespace App\Http\Controllers;

use App\Models\AbonneAssurance;
use App\Models\ReclamationAssurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        $totalRemboursable = ReclamationAssurance::sum('montant_rembourse');

        $assurances = AbonneAssurance::with(['abonne', 'company'])
            ->where('statut', 'actif')
            ->get();

        $selectedAssuranceId = $request->query('assurance_id');

        return view('reclamation_assurances.index', compact(
            'totalReclamations',
            'enAttente',
            'approuvees',
            'remboursees',
            'refusees',
            'totalMontant',
            'totalRemboursable',
            'assurances',
            'selectedAssuranceId'
        ));
    }

    public function getData(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value');
        $statut = $request->input('filters.statut');
        $type = $request->input('filters.type');
        $dateDebut = $request->input('filters.date_debut');
        $dateFin = $request->input('filters.date_fin');
        $assuranceId = $request->input('filters.assurance_id');

        $query = ReclamationAssurance::with(['abonne', 'company']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('abonne', function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('cin', 'like', "%{$search}%");
                })->orWhereHas('company', function ($q2) use ($search) {
                    $q2->where('nom', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($statut)) {
            $query->where('statut', $statut);
        }

        if (! empty($type)) {
            $query->where('type', $type);
        }

        if (! empty($dateDebut)) {
            $query->whereDate('date_reclamation', '>=', $dateDebut);
        }

        if (! empty($dateFin)) {
            $query->whereDate('date_reclamation', '<=', $dateFin);
        }

        if (! empty($assuranceId)) {
            $assurance = AbonneAssurance::find($assuranceId);

            if ($assurance) {
                $query->where('abonne_id', $assurance->abonne_id)
                    ->where('service_id', $assurance->service_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $recordsTotal = ReclamationAssurance::count();
        $recordsFiltered = (clone $query)->count();

        $reclamations = $query->orderByDesc('date_reclamation')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];

        foreach ($reclamations as $index => $reclamation) {
            $abonneNom = trim(($reclamation->abonne->nom ?? '') . ' ' . ($reclamation->abonne->prenom ?? ''));
            $companyNom = $reclamation->company->nom ?? '-';

            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'abonne' => $abonneNom !== '' ? e($abonneNom) : '-',
                'company' => e($companyNom),
                'type' => '<span class="badge badge-info">' . e($reclamation->type_text) . '</span>',
                'montants' => '
                    <div class="text-right">
                        <div><small>Total:</small> ' . number_format((float) $reclamation->montant_total, 2) . ' DH</div>
                        <div><small>Remboursable:</small> ' . number_format((float) $reclamation->montant_rembourse, 2) . ' DH</div>
                    </div>',
                'dates' => '
                    <div class="text-center">
                        <div><small>Reclamation:</small> ' . optional($reclamation->date_reclamation)->format('d/m/Y') . '</div>
                        ' . ($reclamation->date_traitement
                            ? '<div><small>Traitement:</small> ' . $reclamation->date_traitement->format('d/m/Y') . '</div>'
                            : '') . '
                    </div>',
                'statut_badge' => '
                    <span class="badge badge-' . e($reclamation->statut_couleur) . '">
                        ' . e($reclamation->statut_text) . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success traiter-btn" data-id="' . $reclamation->id . '" title="Traiter">
                            <i class="fas fa-check"></i>
                        </button>
                        ' . ($reclamation->justificatif_path
                            ? '<a href="' . e($reclamation->justificatif_url) . '" class="btn btn-primary" target="_blank" title="Justificatif">
                                <i class="fas fa-file"></i>
                            </a>'
                            : '') . '
                        <button class="btn btn-danger delete-btn" data-id="' . $reclamation->id . '" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show(ReclamationAssurance $reclamationAssurance)
    {
        return response()->json([
            'success' => true,
            'data' => $reclamationAssurance->load(['abonne', 'company']),
        ]);
    }

    public function edit(ReclamationAssurance $reclamationAssurance)
    {
        return $this->show($reclamationAssurance);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'abonne_assurance_id' => 'required|exists:subscriptions,id',
            'type' => 'required|in:consultation,examen,medicament,rehabilitation',
            'montant_total' => 'required|numeric|min:0',
            'date_reclamation' => 'required|date',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $assurance = AbonneAssurance::with('company')->findOrFail($request->abonne_assurance_id);
            $tauxCouverture = (float) ($assurance->company->taux_couverture ?? 100);
            $montantRemboursable = ((float) $request->montant_total * $tauxCouverture) / 100;
            $montantRemboursable = min($montantRemboursable, $assurance->solde);

            $data = [
                'abonne_id' => $assurance->abonne_id,
                'service_id' => $assurance->service_id,
                'type' => $request->type,
                'montant_total' => $request->montant_total,
                'montant_rembourse' => $montantRemboursable,
                'date_reclamation' => $request->date_reclamation,
                'statut' => 'en_attente',
                'notes' => $request->notes,
            ];

            if ($request->hasFile('justificatif')) {
                $data['justificatif_path'] = $request->file('justificatif')
                    ->store('reclamations/justificatifs', 'public');
            }

            $reclamation = ReclamationAssurance::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reclamation creee avec succes',
                'reclamation' => $reclamation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, ReclamationAssurance $reclamationAssurance)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:consultation,examen,medicament,rehabilitation',
            'montant_total' => 'required|numeric|min:0',
            'date_reclamation' => 'required|date',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->except('justificatif');

            if ($request->filled('montant_remboursable')) {
                $data['montant_rembourse'] = $request->input('montant_remboursable');
            }

            if ($request->hasFile('justificatif')) {
                if ($reclamationAssurance->justificatif_path) {
                    Storage::disk('public')->delete($reclamationAssurance->justificatif_path);
                }

                $data['justificatif_path'] = $request->file('justificatif')
                    ->store('reclamations/justificatifs', 'public');
            }

            unset($data['montant_remboursable']);
            $reclamationAssurance->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Reclamation mise a jour avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function traiter(Request $request, ReclamationAssurance $reclamationAssurance)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:approuve,refuse,rembourse',
            'date_traitement' => 'required|date',
            'notes_traitement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $notes = trim((string) $reclamationAssurance->notes);
            $traitementNotes = trim((string) $request->notes_traitement);
            $blocTraitement = "Traitement\nStatut: {$request->statut}\nDate: {$request->date_traitement}";

            if ($traitementNotes !== '') {
                $blocTraitement .= "\nNotes: {$traitementNotes}";
            }

            $reclamationAssurance->update([
                'statut' => $request->statut,
                'date_traitement' => $request->date_traitement,
                'notes' => trim($notes . "\n\n" . $blocTraitement),
            ]);

            if (in_array($request->statut, ['approuve', 'rembourse'], true)) {
                $assurance = AbonneAssurance::where('abonne_id', $reclamationAssurance->abonne_id)
                    ->where('service_id', $reclamationAssurance->service_id)
                    ->latest('id')
                    ->first();

                if ($assurance) {
                    $assurance->updateMontantUtilise();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reclamation traitee avec succes',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ReclamationAssurance $reclamationAssurance)
    {
        try {
            if ($reclamationAssurance->justificatif_path) {
                Storage::disk('public')->delete($reclamationAssurance->justificatif_path);
            }

            $assurance = null;

            if (in_array($reclamationAssurance->statut, ['approuve', 'rembourse'], true)) {
                $assurance = AbonneAssurance::where('abonne_id', $reclamationAssurance->abonne_id)
                    ->where('service_id', $reclamationAssurance->service_id)
                    ->latest('id')
                    ->first();
            }

            $reclamationAssurance->delete();

            if ($assurance) {
                $assurance->updateMontantUtilise();
            }

            return response()->json([
                'success' => true,
                'message' => 'Reclamation supprimee avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = ReclamationAssurance::with(['abonne', 'company']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_reclamation', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_reclamation', '<=', $request->date_fin);
        }

        if ($request->filled('assurance_id')) {
            $assurance = AbonneAssurance::find($request->assurance_id);

            if ($assurance) {
                $query->where('abonne_id', $assurance->abonne_id)
                    ->where('service_id', $assurance->service_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $reclamations = $query->orderByDesc('date_reclamation')->get();
        $fileName = 'reclamations_assurance_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($reclamations) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Date reclamation',
                'Abonne',
                'Compagnie',
                'Type',
                'Montant total (DH)',
                'Montant remboursable (DH)',
                'Pourcentage',
                'Statut',
                'Date traitement',
                'Delai traitement (jours)',
                'Notes',
            ], ';');

            foreach ($reclamations as $reclamation) {
                fputcsv($file, [
                    $reclamation->id,
                    optional($reclamation->date_reclamation)->format('d/m/Y'),
                    trim(($reclamation->abonne->nom ?? '') . ' ' . ($reclamation->abonne->prenom ?? '')),
                    $reclamation->company->nom ?? '',
                    $reclamation->type_text,
                    $reclamation->montant_total,
                    $reclamation->montant_rembourse,
                    round($reclamation->pourcentage_remboursement, 2) . '%',
                    $reclamation->statut_text,
                    optional($reclamation->date_traitement)->format('d/m/Y'),
                    $reclamation->delai_traitement ?? '',
                    $reclamation->notes ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
