
@extends('adminlte::page')

@section('title', 'Gestion des Subscriptions')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-id-card"></i> Gestion des Subscriptions</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Subscriptions</li>
            </ol>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-check"></i> Succès!</h5>
            {{ session('success') }}
        </div>
    @endif

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $totalSubscriptions }}</h3>
                                <p>Total Subscriptions</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $totalActifs }}</h3>
                                <p>Subscriptions Actives</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $totalExpires }}</h3>
                                <p>Subscriptions Expirées</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-ban"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $totalExpirant }}</h3>
                                <p>Expirent bientôt</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte principale -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Liste des Subscriptions</h3>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-secondary" id="resetFilters">
                                    <i class="fas fa-redo"></i> Réinitialiser
                                </button>
                                <button class="btn btn-sm btn-info" id="refreshTable">
                                    <i class="fas fa-sync"></i> Actualiser
                                </button>
                                <button class="btn btn-sm btn-success" id="exportBtn">
                                    <i class="fas fa-download"></i> Export
                                </button>
                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                                    <i class="fas fa-plus"></i> Nouvelle Subscription
                                </button>
                            </div>
                        </div>
                        <!-- Filtres avancés -->
                        <div class="row mt-3">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_search">Recherche</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="filter_search" 
                                               placeholder="Nom, activité...">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_statut">Statut</label>
                                    <select class="form-control form-control-sm" id="filter_statut">
                                        <option value="">Tous</option>
                                        <option value="actif">Actif</option>
                                        <option value="expiré">Expiré</option>
                                        <option value="suspendu">Suspendu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_activite">Activité</label>
                                    <select class="form-control form-control-sm" id="filter_activite">
                                        <option value="">Toutes</option>
                                        @foreach($activites as $activite)
                                            <option value="{{ $activite->id }}">{{ $activite->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_type">Type</label>
                                    <select class="form-control form-control-sm" id="filter_type">
                                        <option value="">Tous</option>
                                        <option value="mensuel">Mensuel</option>
                                        <option value="trimestriel">Trimestriel</option>
                                        <option value="annuel">Annuel</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_date_debut">Date début</label>
                                    <input type="date" class="form-control form-control-sm" id="filter_date_debut">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_date_fin">Date fin</label>
                                    <input type="date" class="form-control form-control-sm" id="filter_date_fin">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <table id="subscriptionsTable" class="table table-bordered table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th width="30">#</th>
                                    <th>Abonné</th>
                                    <th width="120">Activité</th>
                                    <th width="80">Type</th>
                                    <th width="150">Dates</th>
                                    <th width="100">Montant</th>
                                    <th width="100">Jours restants</th>
                                    <th width="80">Statut</th>
                                    <th width="140">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            @stop
