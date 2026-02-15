<?php

namespace App\Http\Controllers;

use App\Models\AssuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssuranceCompanyController extends Controller
{
    public function index(Request $request)
    {
        $totalCompanies = AssuranceCompany::count();
        $totalActive = AssuranceCompany::active()->count();
        $tauxMoyen = AssuranceCompany::avg('taux_couverture') ?? 0;
        $delaiMoyen = AssuranceCompany::avg('delai_remboursement') ?? 0;

        return view('assurance_companies.index', compact(
            'totalCompanies', 
            'totalActive', 
            'tauxMoyen',
            'delaiMoyen'
        ));
    }

    public function getData(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search.value');

        $query = AssuranceCompany::withCount(['abonneAssurances' => function($q) {
            $q->where('statut', 'actif');
        }]);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $totalRecords = $query->count();

        $companies = $query->skip($start)
            ->take($length)
            ->orderBy('nom')
            ->get();

        $data = [];
        foreach ($companies as $index => $company) {
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $company->id,
                'nom' => $company->nom,
                'contact' => '
                    <div>
                        <div><i class="fas fa-phone"></i> ' . $company->telephone . '</div>
                        ' . ($company->email ? '<div><i class="fas fa-envelope"></i> ' . $company->email . '</div>' : '') . '
                    </div>',
                'couverture' => '
                    <div class="text-center">
                        <span class="badge badge-info">' . $company->taux_couverture_pourcentage . '</span>
                    </div>',
                'delai' => '
                    <div class="text-center">
                        <span class="badge badge-secondary">' . $company->delai_remboursement_texte . '</span>
                    </div>',
                'assures' => '
                    <div class="text-center">
                        <span class="badge badge-primary">' . $company->abonne_assurances_count . ' assuré(s)</span>
                    </div>',
                'action' => '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info view-btn" data-id="' . $company->id . '">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning edit-btn" data-id="' . $company->id . '">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="' . $company->id . '">
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
            'nom' => 'required|string|max:100|unique:assurance_companies,nom',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:assurance_companies,email',
            'taux_couverture' => 'required|numeric|min:0|max:100',
            'delai_remboursement' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $company = AssuranceCompany::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Compagnie d\'assurance créée avec succès',
                'company' => $company
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, AssuranceCompany $assuranceCompany)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:assurance_companies,nom,' . $assuranceCompany->id,
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:assurance_companies,email,' . $assuranceCompany->id,
            'taux_couverture' => 'required|numeric|min:0|max:100',
            'delai_remboursement' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assuranceCompany->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Compagnie d\'assurance mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(AssuranceCompany $assuranceCompany)
    {
        try {
            // Vérifier s'il y a des assurés actifs
            $assuresActifs = $assuranceCompany->abonneAssurances()->where('statut', 'actif')->count();
            
            if ($assuresActifs > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: compagnie a des assurés actifs'
                ], 400);
            }

            $assuranceCompany->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Compagnie d\'assurance supprimée avec succès'
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
        $companies = AssuranceCompany::withCount(['abonneAssurances' => function($q) {
            $q->where('statut', 'actif');
        }])->orderBy('nom')->get();
        
        $fileName = 'assurance_companies_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($companies) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Nom',
                'Téléphone',
                'Email',
                'Taux Couverture (%)',
                'Délai Remboursement (jours)',
                'Assurés Actifs'
            ], ';');
            
            // Données
            foreach ($companies as $company) {
                fputcsv($file, [
                    $company->id,
                    $company->nom,
                    $company->telephone,
                    $company->email ?? '',
                    $company->taux_couverture,
                    $company->delai_remboursement,
                    $company->abonne_assurances_count
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}