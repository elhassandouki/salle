<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Abonne;
use App\Models\Abonnement;
use App\Models\Activite;
use App\Models\Pointage;
use App\Models\Paiement;
use Carbon\Carbon;
use DB;

class HomeController extends Controller
{
    public function index()
    {
        // Statistiques principales
        $stats = [
            'total_membres' => Abonne::count(),
            'abonnements_actifs' => Abonnement::where('statut', 'actif')
                ->where('date_fin', '>=', today())
                ->count(),
            'entrees_aujourdhui' => Pointage::whereDate('date_pointage', today())->count(),
            'revenu_mois' => $this->getRevenueMois(),
            'membres_nouveaux_mois' => Abonne::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'taux_renouvellement' => $this->calculateRenewalRate(),
        ];
        
        // Données pour les graphiques
        $revenus_mensuels = $this->getRevenusMensuels();
        $repartition_activites = $this->getRepartitionActivites();
        
        // Derniers membres inscrits
        $derniers_membres = Abonne::with('abonnementActif')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Abonnements expirant bientôt (7 prochains jours)
        $abonnements_expirant = Abonnement::with(['abonne', 'activite'])
            ->where('statut', 'actif')
            ->whereBetween('date_fin', [today(), today()->addDays(7)])
            ->orderBy('date_fin')
            ->get();
        
        // Dernières entrées de la journée
        $dernieres_entrees = Pointage::with(['abonne' => function($query) {
                $query->with('abonnementActif.activite');
            }])
            ->whereDate('date_pointage', today())
            ->orderBy('date_pointage', 'desc')
            ->take(10)
            ->get();
        
        return view('home', compact(
            'stats',
            'revenus_mensuels',
            'repartition_activites',
            'derniers_membres',
            'abonnements_expirant',
            'dernieres_entrees'
        ));
    }
    
    /**
     * Calculer le revenu du mois
     */
    private function getRevenueMois()
    {
        return Paiement::whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->sum('montant');
    }
    
    /**
     * Calculer le taux de renouvellement
     */
    private function calculateRenewalRate()
    {
        $totalAbonnements = Abonnement::count();
        $abonnementsRenouveles = Abonnement::whereHas('abonne', function($query) {
            $query->whereHas('abonnements', function($subQuery) {
                $subQuery->where('id', '!=', DB::raw('abonnements.id'));
            });
        })->count();
        
        return $totalAbonnements > 0 ? round(($abonnementsRenouveles / $totalAbonnements) * 100, 2) : 0;
    }
    
    /**
     * Données pour le graphique des revenus mensuels
     */
    private function getRevenusMensuels()
    {
        $revenus = [];
        $mois = [];
        
        // Récupérer les 6 derniers mois
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois[] = $date->translatedFormat('M');
            
            $revenu = Paiement::whereMonth('date_paiement', $date->month)
                ->whereYear('date_paiement', $date->year)
                ->sum('montant');
            
            $revenus[] = $revenu ?? 0;
        }
        
        return [
            'labels' => $mois,
            'data' => $revenus
        ];
    }
    
    /**
     * Répartition des membres par activité
     */
    private function getRepartitionActivites()
    {
        $activites = Activite::withCount(['abonnements' => function($query) {
            $query->where('statut', 'actif')
                ->where('date_fin', '>=', today());
        }])
        ->where('statut', 'actif')
        ->get();
        
        $labels = [];
        $data = [];
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
        ];
        
        foreach ($activites as $index => $activite) {
            $labels[] = $activite->nom;
            $data[] = $activite->abonnements_count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($labels))
        ];
    }
}