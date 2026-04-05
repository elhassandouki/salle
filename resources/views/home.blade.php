@extends('adminlte::page')

@section('title', 'Dashboard - GYM Chahrazad')

@section('content_header')
<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h1>
            <small class="text-muted">Vue d'ensemble de la salle de sport Chahrazad</small>
        </div>
        <div class="text-muted small mt-2 mt-md-0">
            {{ now()->translatedFormat('l d F Y') }} a {{ now()->format('H:i') }}
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-lg-3 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($stats['total_membres']) }}</h3>
                    <p>Membres</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="{{ route('abonnes.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($stats['subscriptions_actifs']) }}</h3>
                    <p>Abonnements actifs</p>
                </div>
                <div class="icon"><i class="fas fa-id-card"></i></div>
                <a href="{{ route('abonnements.index') }}" class="small-box-footer">Ouvrir <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($stats['entrees_aujourdhui']) }}</h3>
                    <p>Entrees aujourd'hui</p>
                </div>
                <div class="icon"><i class="fas fa-door-open"></i></div>
                <a href="{{ route('pointages.index') }}" class="small-box-footer">Voir les pointages <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($stats['revenu_mois'], 2, ',', ' ') }} <small>DH</small></h3>
                    <p>Revenus du mois</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <a href="{{ route('paiements.index') }}" class="small-box-footer">Voir les paiements <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Synthese des revenus</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="status-tile">
                                <div class="label">Moyenne sur 6 mois</div>
                                <div class="value">{{ number_format((float) $revenus_resume['moyenne'], 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="status-tile">
                                <div class="label">Meilleur mois</div>
                                <div class="value">{{ number_format((float) $revenus_resume['maximum'], 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="status-tile">
                                <div class="label">Mois le plus faible</div>
                                <div class="value">{{ number_format((float) $revenus_resume['minimum'], 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th>Revenu</th>
                                    <th>Lecture rapide</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revenus_mensuels['labels'] as $index => $label)
                                    @php
                                        $montant = (float) ($revenus_mensuels['data'][$index] ?? 0);
                                        $reference = (float) $revenus_resume['moyenne'];
                                    @endphp
                                    <tr>
                                        <td class="font-weight-bold">{{ $label }}</td>
                                        <td>{{ number_format($montant, 2, ',', ' ') }} DH</td>
                                        <td>
                                            @if($montant >= $reference && $montant > 0)
                                                <span class="badge badge-success">Bon niveau</span>
                                            @elseif($montant > 0)
                                                <span class="badge badge-warning">A surveiller</span>
                                            @else
                                                <span class="badge badge-secondary">Aucun revenu</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Repartition des activites</h3>
                </div>
                <div class="card-body">
                    @if(count($repartition_activites['labels']) > 0)
                        <div class="mb-3">
                            <div class="status-tile">
                                <div class="label">Total abonnements actifs</div>
                                <div class="value">{{ number_format(array_sum($repartition_activites['data'])) }}</div>
                            </div>
                        </div>

                        @foreach($repartition_activites['labels'] as $index => $label)
                            @php
                                $count = (int) ($repartition_activites['data'][$index] ?? 0);
                                $total = max(1, array_sum($repartition_activites['data']));
                                $percent = round(($count / $total) * 100);
                                $color = $repartition_activites['colors'][$index] ?? '#007bff';
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>{{ $label }}</strong>
                                    <span class="text-muted">{{ $count }} membre(s)</span>
                                </div>
                                <div class="progress progress-lg">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%; background-color: {{ $color }};">
                                        {{ $percent }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-layer-group fa-3x mb-3"></i>
                            <div>Aucune donnee disponible</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-4 col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-user-plus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Nouveaux membres ce mois</span>
                    <span class="info-box-number">{{ $stats['membres_nouveaux_mois'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-dumbbell"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Activites actives</span>
                    <span class="info-box-number">{{ $stats['activites_actives'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-sync-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pointages non synchronises</span>
                    <span class="info-box-number">{{ $stats['pointages_non_sync'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Derniers membres</h3>
                    <a href="{{ route('abonnes.index') }}" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Inscription</th>
                                    <th>Abonnement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($derniers_membres as $membre)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $membre->prenom }} {{ $membre->nom }}</div>
                                            <small class="text-muted">{{ $membre->cin ?: 'Sans CIN' }}</small>
                                        </td>
                                        <td>{{ optional($membre->created_at)->format('d/m/Y') }}</td>
                                        <td>
                                            @if($membre->subscriptionActif && $membre->subscriptionActif->service)
                                                <span class="badge badge-primary">{{ $membre->subscriptionActif->service->nom }}</span>
                                            @else
                                                <span class="badge badge-secondary">Aucun</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Aucun membre recent</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Abonnements a renouveler</h3>
                    <a href="{{ route('abonnements.index') }}" class="btn btn-sm btn-warning">Gerer</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Activite</th>
                                    <th>Fin</th>
                                    <th>Reste</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions_expirant as $abonnement)
                                    <tr>
                                        <td>{{ $abonnement->abonne->prenom }} {{ $abonnement->abonne->nom }}</td>
                                        <td>{{ $abonnement->activite->nom ?? 'N/A' }}</td>
                                        <td>{{ optional($abonnement->date_fin)->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $abonnement->jours_restants <= 3 ? 'danger' : 'warning' }}">
                                                {{ $abonnement->jours_restants }} jour(s)
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Aucun abonnement proche de la fin</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Dernieres entrees</h3>
                    <a href="{{ route('pointages.index') }}" class="btn btn-sm btn-info">Historique</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Heure</th>
                                    <th>Membre</th>
                                    <th>Type</th>
                                    <th>Sync</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dernieres_entrees as $pointage)
                                    <tr>
                                        <td>{{ $pointage->heure }}</td>
                                        <td>{{ optional($pointage->abonne)->prenom }} {{ optional($pointage->abonne)->nom }}</td>
                                        <td><span class="badge badge-{{ $pointage->couleur_type }}">{{ $pointage->type_text }}</span></td>
                                        <td>
                                            <span class="badge badge-{{ $pointage->synced ? 'success' : 'warning' }}">
                                                {{ $pointage->synced ? 'Synchronise' : 'En attente' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Aucune entree aujourd'hui</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Paiements recents</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Membre</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paiements_recents as $paiement)
                                    <tr>
                                        <td>{{ optional($paiement->date_paiement)->format('d/m H:i') }}</td>
                                        <td>{{ optional(optional($paiement->subscription)->abonne)->prenom }} {{ optional(optional($paiement->subscription)->abonne)->nom }}</td>
                                        <td>{{ number_format((float) $paiement->montant, 2, ',', ' ') }} DH</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Aucun paiement recent</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Actions rapides</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-2"><a href="{{ route('abonnes.index') }}" class="btn btn-primary btn-block">Abonnes</a></div>
                        <div class="col-6 mb-2"><a href="{{ route('abonnements.index') }}" class="btn btn-success btn-block">Abonnements</a></div>
                        <div class="col-6 mb-2"><a href="{{ route('paiements.index') }}" class="btn btn-info btn-block">Paiements</a></div>
                        <div class="col-6 mb-2"><a href="{{ route('pointages.index') }}" class="btn btn-warning btn-block">Pointages</a></div>
                        <div class="col-6 mb-2"><a href="{{ route('zk.status') }}" class="btn btn-secondary btn-block">ZKTeco</a></div>
                        <div class="col-6 mb-2"><a href="{{ route('settings.index') }}" class="btn btn-dark btn-block">Parametres</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Etat du systeme</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="status-tile">
                                <div class="label">Base de donnees</div>
                                <div class="value">{{ $system_status['base_donnees'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="status-tile">
                                <div class="label">Pointages en attente de sync</div>
                                <div class="value">{{ $system_status['zk_pending'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="status-tile">
                                <div class="label">Derniere synchronisation</div>
                                <div class="value">{{ $system_status['last_sync_at'] ?: 'Aucune' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.small-box,
.card,
.info-box {
    border-radius: 12px;
}
.status-tile {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    background: #fafbfc;
    height: 100%;
}
.status-tile .label {
    color: #6c757d;
    font-size: .85rem;
    margin-bottom: .35rem;
}
.status-tile .value {
    font-weight: 700;
    font-size: 1.05rem;
}
</style>
@stop

@section('js')
@stop
