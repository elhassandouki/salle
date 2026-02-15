@extends('adminlte::page')

@section('title', 'Assurances des Abonnés')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-user-shield"></i> Assurances des Abonnés</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Assurances Abonnés</li>
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
                    <h3>{{ $totalAssurances }}</h3>
                    <p>Total Assurances</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalActives }}</h3>
                    <p>Assurances Actives</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalExpirees }}</h3>
                    <p>Assurances Expirées</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalResiliees }}</h3>
                    <p>Assurances Résiliées</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Assurances</h3>
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
                        <i class="fas fa-plus"></i> Nouvelle Assurance
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_search">Recherche</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_search" 
                                   placeholder="N° contrat, nom...">
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
                            <option value="resilie">Résilié</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_abonne">Abonné</label>
                        <select class="form-control form-control-sm" id="filter_abonne">
                            <option value="">Tous</option>
                            @foreach($abonnes as $abonne)
                                <option value="{{ $abonne->id }}">
                                    {{ $abonne->nom }} {{ $abonne->prenom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_company">Compagnie</label>
                        <select class="form-control form-control-sm" id="filter_company">
                            <option value="">Toutes</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">
                                    {{ $company->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_plafond">Plafond utilisé</label>
                        <select class="form-control form-control-sm" id="filter_plafond">
                            <option value="">Tous</option>
                            <option value="high">> 80%</option>
                            <option value="medium">50-80%</option>
                            <option value="low">< 50%</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="assurancesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Abonné</th>
                        <th width="120">Compagnie</th>
                        <th width="150">Contrat</th>
                        <th width="150">Plafond annuel</th>
                        <th width="100">Jours restants</th>
                        <th width="80">Statut</th>
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

{{-- Modal Ajout Assurance --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouvelle Assurance
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Abonné *</label>
                                <select name="abonne_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Sélectionner un abonné</option>
                                    @foreach($abonnes as $abonne)
                                        <option value="{{ $abonne->id }}">
                                            {{ $abonne->nom }} {{ $abonne->prenom }} 
                                            ({{ $abonne->cin ?? 'N/C' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Compagnie d'assurance *</label>
                                <select name="assurance_company_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Sélectionner une compagnie</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" data-taux="{{ $company->taux_couverture }}">
                                            {{ $company->nom }} ({{ $company->taux_couverture }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>N° de contrat *</label>
                                <input type="text" name="numero_contrat" class="form-control" required 
                                       placeholder="Numéro du contrat" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Plafond annuel (DH) *</label>
                                <input type="number" name="plafond_annuel" class="form-control" required 
                                       min="0" step="0.01" placeholder="5000.00">
                                <small class="text-muted">Plafond annuel de remboursement</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date de début *</label>
                                <input type="date" name="date_debut" class="form-control" required 
                                       value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date de fin *</label>
                                <input type="date" name="date_fin" class="form-control" required 
                                       value="{{ date('Y-m-d', strtotime('+1 year')) }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Statut *</label>
                        <select name="statut" class="form-control" required>
                            <option value="actif">Actif</option>
                            <option value="expiré">Expiré</option>
                            <option value="resilie">Résilié</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Notes supplémentaires..."></textarea>
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

{{-- Modal Réclamation --}}
<div class="modal fade" id="reclamationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-file-medical"></i> Nouvelle Réclamation
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="reclamationModalContent">
                <!-- Chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

{{-- Modal Renouvellement --}}
<div class="modal fade" id="renouvelerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-redo"></i> Renouveler l'Assurance
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="renouvelerForm">
                @csrf
                <input type="hidden" name="assurance_id" id="renouveler_assurance_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nouveau numéro de contrat</label>
                        <input type="text" name="numero_contrat" class="form-control" 
                               placeholder="Numéro du nouveau contrat">
                        <small class="text-muted">Laisser vide pour générer automatiquement</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nouvelle date de fin *</label>
                                <input type="date" name="date_fin" class="form-control" required 
                                       value="{{ date('Y-m-d', strtotime('+1 year')) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nouveau plafond annuel (DH) *</label>
                                <input type="number" name="plafond_annuel" class="form-control" required 
                                       min="0" step="0.01" value="5000.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info" id="submitRenouvelerBtn">
                        <i class="fas fa-redo"></i> Renouveler
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
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">
                    <i class="fas fa-trash"></i> Supprimer l'Assurance
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette assurance ?</p>
                <p class="text-danger"><strong>Cette action est irréversible !</strong></p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
.small-box>.inner { padding: 10px; }
.small-box h3 { font-size: 38px; font-weight: bold; margin: 0 0 10px 0; }
.small-box .icon { position: absolute; top: -10px; right: 10px; font-size: 90px; color: rgba(0,0,0,0.15); }
.select2-container { width: 100% !important; }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Variables globales
    var table = null;
    
    // Initialiser Select2
    $('.select2').select2({
        dropdownParent: $('#addModal')
    });
    
    // Fonction pour afficher les notifications
    function showToast(message, type = 'info') {
        let toastClass = 'bg-info';
        switch(type) {
            case 'success': toastClass = 'bg-success'; break;
            case 'error': toastClass = 'bg-danger'; break;
            case 'warning': toastClass = 'bg-warning'; break;
        }
        
        let toast = $(`
            <div class="toast ${toastClass} text-white" style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; min-width: 300px;">
                <div class="toast-body">
                    <button type="button" class="close text-white ml-2 mb-1" style="float: right;">
                        <span>&times;</span>
                    </button>
                    ${message}
                </div>
            </div>
        `);
        
        $('body').append(toast);
        
        toast.find('.close').on('click', function() {
            $(this).closest('.toast').remove();
        });
        
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Initialiser DataTable
    function initializeDataTable() {
        table = $('#assurancesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('abonne_assurances.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        statut: $('#filter_statut').val(),
                        abonne_id: $('#filter_abonne').val(),
                        company_id: $('#filter_company').val(),
                        plafond: $('#filter_plafond').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'abonne', name: 'abonne', orderable: false },
                { data: 'company', name: 'company', orderable: false },
                { data: 'contrat', name: 'numero_contrat', orderable: false },
                { data: 'plafond', name: 'plafond_annuel', orderable: false },
                { data: 'jours_restants', name: 'jours_restants', orderable: false, searchable: false },
                { data: 'statut_badge', name: 'statut', orderable: false },
                { data: 'action', orderable: false, searchable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
            order: [[0, 'desc']],
            responsive: true,
            language: {
                processing:     "Traitement en cours...",
                search:         "Rechercher&nbsp;:",
                lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
                info:           "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                infoEmpty:      "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
                infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                infoPostFix:    "",
                loadingRecords: "Chargement en cours...",
                zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                emptyTable:     "Aucune donn&eacute;e disponible dans le tableau",
                paginate: {
                    first:      "Premier",
                    previous:   "Pr&eacute;c&eacute;dent",
                    next:       "Suivant",
                    last:       "Dernier"
                }
            },
            drawCallback: function(settings) {
                if (settings.json && settings.json.recordsTotal !== undefined) {
                    var api = this.api();
                    var pageInfo = api.page.info();
                    $('#tableInfo').text(
                        'Affichage de ' + (pageInfo.start + 1) + ' à ' + 
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' assurances'
                    );
                }
            }
        });
    }
    
    // Initialiser la table
    initializeDataTable();
    
    // Gestion des filtres
    $('#filter_search').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    $('#filter_statut, #filter_abonne, #filter_company, #filter_plafond').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_statut').val('');
        $('#filter_abonne').val('');
        $('#filter_company').val('');
        $('#filter_plafond').val('');
        table.search('').draw();
    });
    
    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
        showToast('Table actualisée', 'success');
    });
    
    // ==================== MODALE AJOUT ====================
    // Soumission du formulaire d'ajout
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#submitAddBtn');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        
        $.ajax({
            url: "{{ route('abonne_assurances.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    $('#addForm')[0].reset();
                    $('.select2').val(null).trigger('change');
                } else {
                    showToast(response.message || 'Erreur lors de la création', 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('[name="' + key + '"]').addClass('is-invalid')
                            .after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                    showToast('Veuillez corriger les erreurs', 'warning');
                } else {
                    showToast('Une erreur est survenue', 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE RÉCLAMATION ====================
    $(document).on('click', '.reclamation-btn', function() {
        let id = $(this).data('id');
        
        // Rediriger vers la page des réclamations avec filtre
        window.location.href = "{{ route('reclamation_assurances.index') }}?assurance_id=" + id;
    });
    
    // ==================== MODALE RENOUVELLEMENT ====================
    $(document).on('click', '.renouveler-btn', function() {
        let id = $(this).data('id');
        $('#renouveler_assurance_id').val(id);
        $('#renouvelerModal').modal('show');
    });
    
    $('#renouvelerForm').on('submit', function(e) {
        e.preventDefault();
        let assuranceId = $('#renouveler_assurance_id').val();
        let submitBtn = $('#submitRenouvelerBtn');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Renouvellement...');
        
        $.ajax({
            url: "{{ url('abonne-assurances') }}/" + assuranceId + "/renouveler",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#renouvelerModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    $('#renouvelerForm')[0].reset();
                } else {
                    showToast(response.message, 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                showToast('Erreur lors du renouvellement', 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE SUPPRESSION ====================
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        $('#delete_id').val(id);
        $('#deleteModal').modal('show');
    });
    
    $('#confirmDelete').on('click', function() {
        let id = $('#delete_id').val();
        let btn = $(this);
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');
        
        $.ajax({
            url: "{{ url('abonne-assurances') }}/" + id,
            type: "DELETE",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                showToast('Erreur lors de la suppression', 'error');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Export
    $('#exportBtn').on('click', function() {
        let filters = {
            statut: $('#filter_statut').val(),
            abonne_id: $('#filter_abonne').val(),
            company_id: $('#filter_company').val()
        };
        
        let queryString = $.param(filters);
        window.location.href = "{{ route('abonne_assurances.export') }}?" + queryString;
    });
});
</script>
@stop