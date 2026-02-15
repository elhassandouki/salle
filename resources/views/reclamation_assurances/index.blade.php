@extends('adminlte::page')

@section('title', 'Réclamations d\'Assurance')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-file-medical"></i> Réclamations d'Assurance</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Réclamations</li>
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
                    <h3>{{ $totalReclamations }}</h3>
                    <p>Total Réclamations</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $enAttente }}</h3>
                    <p>En attente</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $approuvees }}</h3>
                    <p>Approuvées</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totalMontant, 2) }} DH</h3>
                    <p>Total réclamé</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Réclamations</h3>
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
                        <i class="fas fa-plus"></i> Nouvelle Réclamation
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mt-3">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_search">Recherche</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_search" 
                                   placeholder="Nom, notes...">
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
                            <option value="en_attente">En attente</option>
                            <option value="approuve">Approuvé</option>
                            <option value="refuse">Refusé</option>
                            <option value="rembourse">Remboursé</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_type">Type</label>
                        <select class="form-control form-control-sm" id="filter_type">
                            <option value="">Tous</option>
                            <option value="consultation">Consultation</option>
                            <option value="examen">Examen</option>
                            <option value="medicament">Médicament</option>
                            <option value="rehabilitation">Réhabilitation</option>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_assurance">Assurance</label>
                        <select class="form-control form-control-sm" id="filter_assurance">
                            <option value="">Toutes</option>
                            @foreach($assurances as $assurance)
                                <option value="{{ $assurance->id }}">
                                    {{ $assurance->abonne->nom }} - {{ $assurance->company->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="reclamationsTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Abonné</th>
                        <th width="120">Compagnie</th>
                        <th width="100">Type</th>
                        <th width="150">Montants</th>
                        <th width="120">Dates</th>
                        <th width="100">Statut</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
        <div class="card-footer">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                <span id="tableInfo">Chargement des données...</span>
            </small>
        </div>
    </div>
</div>

<!-- ==================== MODALES ==================== -->

{{-- Modal Ajout Réclamation --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouvelle Réclamation
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Assurance *</label>
                                <select name="abonne_assurance_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Sélectionner une assurance</option>
                                    @foreach($assurances as $assurance)
                                        <option value="{{ $assurance->id }}" 
                                                data-taux="{{ $assurance->company->taux_couverture }}"
                                                data-plafond="{{ $assurance->plafond_annuel }}"
                                                data-utilise="{{ $assurance->montant_utilise }}">
                                            {{ $assurance->abonne->nom }} {{ $assurance->abonne->prenom }} - 
                                            {{ $assurance->company->nom }} 
                                            (Plafond: {{ $assurance->plafond_annuel }} DH, 
                                            Utilisé: {{ $assurance->montant_utilise }} DH)
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted" id="assurance_info"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="consultation">Consultation</option>
                                    <option value="examen">Examen médical</option>
                                    <option value="medicament">Médicament</option>
                                    <option value="rehabilitation">Réhabilitation</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant total (DH) *</label>
                                <input type="number" name="montant_total" class="form-control" required 
                                       min="0" step="0.01" placeholder="0.00" id="montant_total">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant remboursable (DH)</label>
                                <input type="number" name="montant_remboursable" class="form-control" readonly 
                                       id="montant_remboursable" placeholder="Calculé automatiquement">
                                <small class="text-muted" id="remboursement_info"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date de réclamation *</label>
                                <input type="date" name="date_reclamation" class="form-control" required 
                                       value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Justificatif</label>
                                <div class="custom-file">
                                    <input type="file" name="justificatif" class="custom-file-input" 
                                           id="justificatif" accept=".pdf,.jpg,.jpeg,.png">
                                    <label class="custom-file-label" for="justificatif">Choisir un fichier</label>
                                </div>
                                <small class="text-muted">PDF, JPG, PNG (max 5MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Détails de la réclamation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitAddBtn">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Traitement Réclamation --}}
<div class="modal fade" id="traiterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-check"></i> Traiter la Réclamation
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="traiterForm">
                @csrf
                <input type="hidden" name="reclamation_id" id="traiter_reclamation_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nouveau statut *</label>
                        <select name="statut" class="form-control" required>
                            <option value="approuve">Approuver</option>
                            <option value="refuse">Refuser</option>
                            <option value="rembourse">Marquer comme remboursé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de traitement *</label>
                        <input type="date" name="date_traitement" class="form-control" required 
                               value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Notes de traitement</label>
                        <textarea name="notes_traitement" class="form-control" rows="3" 
                                  placeholder="Raison de la décision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="submitTraiterBtn">
                        <i class="fas fa-check"></i> Traiter
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Suppression --}}
<div class="