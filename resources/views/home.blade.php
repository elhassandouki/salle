@extends('adminlte::page')

@section('title', 'Tableau de bord - GYM Management')

@section('content')
<div class="content">
    <div class="container-fluid">
        <!-- En-tête de page -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">
                            <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">
                                <i class="fas fa-home"></i> Accueil
                                <span class="ml-2 text-muted" id="current-date">
                                    {{ now()->translatedFormat('l d F Y') }} à {{ now()->format('H:i') }}
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($stats['total_membres']) }}</h3>
                        <p>Membres totaux</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Voir détails <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($stats['abonnements_actifs']) }}</h3>
                        <p>Abonnements actifs</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Voir détails <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($stats['entrees_aujourdhui']) }}</h3>
                        <p>Entrées aujourd'hui</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Voir détails <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($stats['revenu_mois'], 2, ',', ' ') }} <small>MAD</small></h3>
                        <p>Revenu ce mois</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Voir détails <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Graphiques principaux -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title m-0">
                                <i class="fas fa-chart-line mr-2"></i>
                                Revenus mensuels
                            </h3>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="6">
                                    6 mois
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-period="12">
                                    12 mois
                                </button>
                            </div>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="revenusChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title m-0">
                            <i class="fas fa-chart-pie mr-2"></i>
                            Répartition par activité
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(count($repartition_activites['labels']) > 0)
                            <canvas id="activitesChart" height="250"></canvas>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucune donnée disponible</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Section infos rapides -->
        <div class="row">
            <!-- Derniers membres inscrits -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title m-0">
                                <i class="fas fa-user-plus mr-2"></i>
                                Derniers membres inscrits
                            </h3>
                            <span class="badge badge-primary">{{ $derniers_membres->count() }}</span>
                        </div>
                        <div class="card-tools">
                            <a href="#" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Nouveau
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-valign-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Membre</th>
                                        <th>Date d'inscription</th>
                                        <th>Activité</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($derniers_membres as $membre)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="img-circle elevation-1 bg-primary d-flex align-items-center justify-content-center" 
                                                     style="width: 35px; height: 35px; margin-right: 10px;">
                                                    @if($membre->photo)
                                                        <img src="{{ asset('storage/' . $membre->photo) }}" 
                                                             alt="{{ $membre->prenom }}" 
                                                             class="img-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                                    @else
                                                        <i class="fas fa-user text-white"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold">{{ $membre->prenom }} {{ $membre->nom }}</div>
                                                    <small class="text-muted">{{ $membre->cin ?? 'Sans CIN' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $membre->created_at->format('d/m/Y') }}</span>
                                        </td>
                                        <td>
                                            @if($membre->abonnementActif)
                                                <span class="badge" style="background-color: {{ $membre->abonnementActif->activite->couleur ?? '#007bff' }}">
                                                    {{ $membre->abonnementActif->activite->nom ?? 'Non défini' }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">Sans abonnement</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-sm btn-info" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-success" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">Aucun membre trouvé</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($derniers_membres->count() > 0)
                    <div class="card-footer text-center">
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            Voir tous les membres <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Abonnements expirant bientôt -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title m-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Abonnements expirant bientôt
                            </h3>
                            <span class="badge badge-warning">{{ $abonnements_expirant->count() }}</span>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-toggle="tooltip" title="Délai: 7 jours">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-valign-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Membre</th>
                                        <th>Activité</th>
                                        <th>Expire le</th>
                                        <th>Jours restants</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($abonnements_expirant as $abonnement)
                                    @php
                                        $joursRestants = today()->diffInDays($abonnement->date_fin, false);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $abonnement->abonne->prenom }} {{ $abonnement->abonne->nom }}</div>
                                            <small class="text-muted">{{ $abonnement->abonne->telephone }}</small>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $abonnement->activite->couleur ?? '#007bff' }}">
                                                {{ $abonnement->activite->nom }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="{{ $joursRestants <= 3 ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $abonnement->date_fin->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($joursRestants <= 3)
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $joursRestants }} jours
                                                </span>
                                            @elseif($joursRestants <= 7)
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ $joursRestants }} jours
                                                </span>
                                            @else
                                                <span class="badge badge-info">{{ $joursRestants }} jours</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-sm btn-success" title="Renouveler">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-warning" title="Envoyer rappel">
                                                    <i class="fas fa-bell"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                            <p class="text-muted mb-0">Aucun abonnement n'expire bientôt</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($abonnements_expirant->count() > 0)
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                {{ $abonnements_expirant->where('jours_restants', '<=', 3)->count() }} expiration(s) critique(s)
                            </small>
                            <a href="#" class="btn btn-sm btn-outline-warning">
                                Gérer les expirations <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dernières activités -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title m-0">
                                <i class="fas fa-history mr-2"></i>
                                Dernières entrées aujourd'hui
                            </h3>
                            <span class="badge badge-info">{{ $dernieres_entrees->count() }}</span>
                        </div>
                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <input type="text" name="table_search" class="form-control" placeholder="Rechercher un membre...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        @if($dernieres_entrees->count() > 0)
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Heure</th>
                                        <th>Membre</th>
                                        <th>Activité</th>
                                        <th>Type d'entrée</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dernieres_entrees as $entree)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold">{{ $entree->date_pointage->format('H:i') }}</span>
                                            <br>
                                            <small class="text-muted">{{ $entree->date_pointage->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="img-circle elevation-1 bg-info d-flex align-items-center justify-content-center mr-2" 
                                                     style="width: 30px; height: 30px;">
                                                    <i class="fas fa-user text-white" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold">{{ $entree->abonne->prenom }} {{ $entree->abonne->nom }}</div>
                                                    <small class="text-muted">{{ $entree->abonne->cin ?? 'Sans CIN' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($entree->abonne->abonnementActif && $entree->abonne->abonnementActif->activite)
                                                <span class="badge" style="background-color: {{ $entree->abonne->abonnementActif->activite->couleur ?? '#007bff' }}">
                                                    {{ $entree->abonne->abonnementActif->activite->nom }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">Non défini</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $entree->type == 'entree' ? 'badge-success' : 'badge-warning' }}">
                                                {{ $entree->type == 'entree' ? 'Entrée' : 'Sortie' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($entree->synced)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check mr-1"></i> Synchronisé
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-sync-alt mr-1"></i> En attente
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info" title="Détails">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" title="Corriger">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-door-closed fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">Aucune entrée aujourd'hui</h4>
                                <p class="text-muted">Les membres n'ont pas encore pointé aujourd'hui.</p>
                            </div>
                        @endif
                    </div>
                    @if($dernieres_entrees->count() > 0)
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Dernière mise à jour: {{ now()->format('H:i:s') }}
                                </small>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-sync-alt mr-1"></i> Actualiser
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt mr-2"></i>
                            Actions rapides
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-primary">
                                    <i class="fas fa-user-plus fa-2x"></i>
                                    <strong>Nouveau membre</strong>
                                    <small>Inscrire un nouveau</small>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-success">
                                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                                    <strong>Nouveau paiement</strong>
                                    <small>Enregistrer un paiement</small>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-info">
                                    <i class="fas fa-print fa-2x"></i>
                                    <strong>Imprimer facture</strong>
                                    <small>Générer une facture</small>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-warning">
                                    <i class="fas fa-sync-alt fa-2x"></i>
                                    <strong>Sync ZKTeco</strong>
                                    <small>Synchroniser les données</small>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-danger">
                                    <i class="fas fa-chart-bar fa-2x"></i>
                                    <strong>Rapport du jour</strong>
                                    <small>Générer un rapport</small>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <a href="#" class="btn btn-app bg-secondary">
                                    <i class="fas fa-cog fa-2x"></i>
                                    <strong>Paramètres</strong>
                                    <small>Configurer le système</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations système -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card card-outline card-default">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Informations système
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-database"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Base de données</span>
                                        <span class="info-box-number">MySQL</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" style="width: 70%"></div>
                                        </div>
                                        <span class="progress-description">
                                            50 membres enregistrés
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">ZKTeco F18</span>
                                        <span class="info-box-number">Connecté</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: 90%"></div>
                                        </div>
                                        <span class="progress-description">
                                            {{ $stats['entrees_aujourdhui'] }} entrées aujourd'hui
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-print"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Imprimante thermique</span>
                                        <span class="info-box-number">Prête</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Impression automatique activée
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-danger">
                                        <i class="fas fa-shield-alt"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Sécurité</span>
                                        <span class="info-box-number">Active</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Dernière connexion: {{ auth()->user()->updated_at->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour l'heure en temps réel
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR');
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
            const datePart = dateElement.textContent.split('à')[0];
            dateElement.textContent = datePart + 'à ' + timeString;
        }
    }
    
    // Mettre à jour toutes les 30 secondes
    updateTime();
    setInterval(updateTime, 30000);

    // Graphique des revenus
    const revenusCtx = document.getElementById('revenusChart');
    if (revenusCtx) {
        const revenusChart = new Chart(revenusCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($revenus_mensuels['labels']),
                datasets: [{
                    label: 'Revenus (MAD)',
                    data: @json($revenus_mensuels['data']),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('fr-FR') + ' MAD';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' MAD';
                            },
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Boutons de période
        document.querySelectorAll('[data-period]').forEach(button => {
            button.addEventListener('click', function() {
                // Mettre à jour l'état actif
                document.querySelectorAll('[data-period]').forEach(btn => {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('active', 'btn-primary');
                
                // Ici tu pourrais ajouter une requête AJAX pour changer la période
                const period = this.dataset.period;
                console.log('Changer la période à:', period + ' mois');
            });
        });
    }

    // Graphique des activités
    const activitesCtx = document.getElementById('activitesChart');
    if (activitesCtx && @json(count($repartition_activites['labels']) > 0)) {
        const activitesChart = new Chart(activitesCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($repartition_activites['labels']),
                datasets: [{
                    data: @json($repartition_activites['data']),
                    backgroundColor: @json($repartition_activites['colors']),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            font: {
                                size: 11
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} membres (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Initialiser les tooltips Bootstrap
    $('[data-toggle="tooltip"]').tooltip();

    // Auto-refresh des données toutes les 5 minutes
    setTimeout(function() {
        // Ici tu pourrais ajouter une requête AJAX pour rafraîchir les données
        console.log('Auto-refresh des données...');
    }, 300000); // 5 minutes
});
</script>
@endpush

@push('styles')
<style>
.small-box {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
}
.small-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.small-box .icon {
    transition: all 0.3s ease;
}
.small-box:hover .icon {
    transform: scale(1.1);
}
.btn-app {
    border-radius: 10px;
    padding: 20px 10px;
    height: 130px;
    min-width: 130px;
    margin: 0 5px 15px;
    color: white;
    transition: all 0.3s ease;
    border: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.btn-app:hover {
    transform: translateY(-3px) scale(1.03);
    color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.btn-app strong {
    font-size: 14px;
    margin-top: 10px;
    display: block;
}
.btn-app small {
    font-size: 11px;
    opacity: 0.9;
    margin-top: 5px;
}
.badge {
    font-weight: 500;
    padding: 5px 10px;
    border-radius: 20px;
}
.table-valign-middle td {
    vertical-align: middle;
}
.img-circle {
    border-radius: 50%;
    overflow: hidden;
}
.info-box {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.1);
}
.card {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.1);
}
.card-header {
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
}
.progress {
    height: 8px;
    border-radius: 4px;
}
</style>
@endpush
@endsection