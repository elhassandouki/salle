<?php

namespace App\Http\Controllers;

use App\Models\Abonne;
use App\Models\AbonneAssurance;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AbonneAssuranceController extends Controller
{
    public function index(Request $request)
    {
        $totalAssurances = AbonneAssurance::count();
        $totalActives = AbonneAssurance::where('statut', 'actif')->count();
        $totalExpirees = AbonneAssurance::expires()->count();
        $totalResiliees = AbonneAssurance::where('statut', 'resilie')->count();
        $abonnes = Abonne::orderBy('nom')->orderBy('prenom')->get();

        return view('abonne_assurances.index', compact(
            'totalAssurances',
            'totalActives',
            'totalExpirees',
            'totalResiliees',
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
        $abonneId = $request->input('filters.abonne_id');
        $type = $request->input('filters.type_abonnement');

        $query = AbonneAssurance::with('abonne');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%")
                    ->orWhereHas('abonne', function ($q2) use ($search) {
                        $q2->where('nom', 'LIKE', "%{$search}%")
                            ->orWhere('prenom', 'LIKE', "%{$search}%")
                            ->orWhere('cin', 'LIKE', "%{$search}%");
                    });
            });
        }

        if (! empty($statut)) {
            if ($statut === 'expire') {
                $query->expires();
            } else {
                $query->where('statut', $statut);
            }
        }

        if (! empty($abonneId)) {
            $query->where('abonne_id', $abonneId);
        }

        if (! empty($type)) {
            $query->where('type_abonnement', $type);
        }

        $totalRecords = $query->count();

        $assurances = $query->skip($start)
            ->take($length)
            ->orderBy('date_debut', 'desc')
            ->get();

        $data = [];

        foreach ($assurances as $index => $assurance) {
            $joursRestants = $assurance->jours_restants;
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $assurance->id,
                'abonne' => $assurance->abonne->nom . ' ' . $assurance->abonne->prenom,
                'duree' => ucfirst((string) $assurance->type_abonnement),
                'contrat' => '
                    <div>
                        <strong>' . e($assurance->numero_contrat) . '</strong>
                        <div><small>Du ' . $assurance->date_debut->format('d/m/Y') . ' au ' .
                            $assurance->date_fin->format('d/m/Y') . '</small></div>
                    </div>',
                'montant' => '
                    <div class="text-center">
                        <div><strong>' . number_format((float) $assurance->montant_total, 2) . ' DH</strong></div>
                        <div><small>Paye:</small> ' . number_format((float) $assurance->montant_paye, 2) . ' DH</div>
                        <div><small>Reste:</small> ' . number_format((float) $assurance->reste, 2) . ' DH</div>
                    </div>',
                'jours_restants' => $joursRestants > 0
                    ? '<span class="badge badge-' . ($joursRestants <= 30 ? 'warning' : 'success') . '">' . $joursRestants . ' jour(s)</span>'
                    : '<span class="badge badge-secondary">Expire</span>',
                'statut_badge' => '
                    <span class="badge badge-' . $assurance->statut_couleur . '">
                        ' . ucfirst($this->normalizeStatus($assurance->statut)) . '
                    </span>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success reclamation-btn" data-id="' . $assurance->id . '" title="Reclamation">
                            <i class="fas fa-file-medical"></i>
                        </button>
                        <button class="btn btn-info renouveler-btn" data-id="' . $assurance->id . '" title="Renouveler">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $assurance->id . '" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>',
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'abonne_id' => 'required|exists:abonnes,id',
            'type_abonnement' => 'required|in:mensuel,trimestriel,semestriel,annuel',
            'date_debut' => 'required|date',
            'montant_assurance' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expire,resilie',
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
            $existing = AbonneAssurance::where('abonne_id', $request->abonne_id)
                ->where('statut', 'actif')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'L abonne a deja une assurance active.',
                ], 400);
            }

            $service = $this->resolveGenericAssuranceService();
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin = $this->calculateInsuranceEndDate($dateDebut, $request->type_abonnement);

            $assurance = AbonneAssurance::create([
                'abonne_id' => $request->abonne_id,
                'service_id' => $service->id,
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $request->montant_assurance,
                'montant_total' => $request->montant_assurance,
                'montant_paye' => 0,
                'reste' => $request->montant_assurance,
                'statut' => $this->normalizeStatus($request->statut),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assurance creee avec succes',
                'assurance' => $assurance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, AbonneAssurance $abonneAssurance)
    {
        $validator = Validator::make($request->all(), [
            'type_abonnement' => 'required|in:mensuel,trimestriel,semestriel,annuel',
            'date_debut' => 'required|date',
            'montant_assurance' => 'required|numeric|min:0',
            'statut' => 'required|in:actif,expire,resilie',
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
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin = $this->calculateInsuranceEndDate($dateDebut, $request->type_abonnement);

            $abonneAssurance->update([
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $request->montant_assurance,
                'montant_total' => $request->montant_assurance,
                'reste' => max(0, $request->montant_assurance - $abonneAssurance->montant_paye),
                'statut' => $this->normalizeStatus($request->statut),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assurance mise a jour avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(AbonneAssurance $abonneAssurance)
    {
        try {
            $reclamationsCount = $abonneAssurance->reclamations()->count();

            if ($reclamationsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: assurance a des reclamations associees',
                ], 400);
            }

            $abonneAssurance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assurance supprimee avec succes',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function renouveler(Request $request, AbonneAssurance $abonneAssurance)
    {
        $validator = Validator::make($request->all(), [
            'type_abonnement' => 'required|in:mensuel,trimestriel,semestriel,annuel',
            'montant_assurance' => 'required|numeric|min:0',
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

            $abonneAssurance->update(['statut' => 'expire']);
            $dateDebut = Carbon::parse($abonneAssurance->date_fin)->addDay();
            $dateFin = $this->calculateInsuranceEndDate($dateDebut, $request->type_abonnement);

            $nouvelleAssurance = AbonneAssurance::create([
                'abonne_id' => $abonneAssurance->abonne_id,
                'service_id' => $abonneAssurance->service_id ?: $this->resolveGenericAssuranceService()->id,
                'type_abonnement' => $request->type_abonnement,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'montant' => $request->montant_assurance,
                'montant_total' => $request->montant_assurance,
                'montant_paye' => 0,
                'reste' => $request->montant_assurance,
                'statut' => 'actif',
                'notes' => trim('Renouvellement assurance' . PHP_EOL . ($request->notes ?? '')),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assurance renouvelee avec succes',
                'assurance' => $nouvelleAssurance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = AbonneAssurance::with('abonne');

        if ($request->filled('statut')) {
            if ($request->statut === 'expire') {
                $query->expires();
            } else {
                $query->where('statut', $request->statut);
            }
        }

        if ($request->filled('abonne_id')) {
            $query->where('abonne_id', $request->abonne_id);
        }

        if ($request->filled('type_abonnement')) {
            $query->where('type_abonnement', $request->type_abonnement);
        }

        $assurances = $query->orderBy('date_debut', 'desc')->get();
        $fileName = 'abonne_assurances_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($assurances) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Abonne',
                'Duree',
                'Date debut',
                'Date fin',
                'Montant (DH)',
                'Montant paye (DH)',
                'Reste (DH)',
                'Statut',
                'Notes',
            ], ';');

            foreach ($assurances as $assurance) {
                fputcsv($file, [
                    $assurance->id,
                    $assurance->abonne->nom . ' ' . $assurance->abonne->prenom,
                    $assurance->type_abonnement,
                    $assurance->date_debut->format('d/m/Y'),
                    $assurance->date_fin->format('d/m/Y'),
                    $assurance->montant_total,
                    $assurance->montant_paye,
                    $assurance->reste,
                    ucfirst($this->normalizeStatus($assurance->statut)),
                    $assurance->notes ?? '',
                ], ';');
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    protected function resolveGenericAssuranceService(): Service
    {
        return Service::firstOrCreate(
            ['type' => 'assurance', 'nom' => 'Assurance'],
            [
                'description' => 'Service generique pour les assurances',
                'prix_mensuel' => 0,
                'prix_trimestriel' => 0,
                'prix_annuel' => 0,
                'statut' => 'actif',
                'couleur' => '#17a2b8',
            ]
        );
    }

    protected function calculateInsuranceEndDate(Carbon $dateDebut, string $type): Carbon
    {
        return match ($type) {
            'mensuel' => $dateDebut->copy()->addMonth(),
            'trimestriel' => $dateDebut->copy()->addMonths(3),
            'semestriel' => $dateDebut->copy()->addMonths(6),
            'annuel' => $dateDebut->copy()->addYear(),
            default => $dateDebut->copy()->addMonths(3),
        };
    }

    protected function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'expire', 'expiré', 'expirأ©', 'expirط£آ©' => 'expire',
            default => $status ?: 'actif',
        };
    }
}
