@extends('adminlte::page')

@section('title', 'Compagnies d\'Assurance')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-shield-alt"></i> Compagnies d'Assurance</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Assurances</li>
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
                    <h3>{{ $totalCompanies }}</h3>
                    <p>Total Compagnies</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalActive }}</h3>
                    <p>Compagnies Actives</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($tauxMoyen, 1) }}%</h3>
                    <p>Taux Moyen</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($delaiMoyen, 0) }}j</h3>
                    <p>Délai Moyen</p>
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
                <h3 class="card-title">Liste des Compagnies d'Assurance</h3>
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
                        <i class="fas fa-plus"></i> Nouvelle Compagnie
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="filter_search">Recherche</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_search" 
                                   placeholder="Nom, téléphone, email...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_taux_min">Taux min (%)</label>
                        <input type="number" class="form-control form-control-sm" id="filter_taux_min" 
                               placeholder="0" min="0" max="100">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_taux_max">Taux max (%)</label>
                        <input type="number" class="form-control form-control-sm" id="filter_taux_max" 
                               placeholder="100" min="0" max="100">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="companiesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Nom</th>
                        <th width="150">Contact</th>
                        <th width="100">Couverture</th>
                        <th width="100">Délai</th>
                        <th width="100">Assurés</th>
                        <th width="120">Actions</th>
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

{{-- Modal Ajout Compagnie --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouvelle Compagnie d'Assurance
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" class="form-control" required 
                               placeholder="Nom de la compagnie" maxlength="100">
                        <div class="invalid-feedback" id="nom_error"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Téléphone *</label>
                                <input type="text" name="telephone" class="form-control" required 
                                       placeholder="Ex: 0522123456" maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="contact@compagnie.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Taux de couverture (%) *</label>
                                <input type="number" name="taux_couverture" class="form-control" required 
                                       min="0" max="100" step="0.01" value="80.00">
                                <small class="text-muted">Pourcentage remboursé par la compagnie</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Délai de remboursement (jours) *</label>
                                <input type="number" name="delai_remboursement" class="form-control" required 
                                       min="1" value="30">
                                <small class="text-muted">Délai moyen en jours</small>
                            </div>
                        </div>
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

{{-- Modal Éditer Compagnie --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Modifier la Compagnie
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="editModalContent">
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                    Chargement...
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Suppression --}}
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">
                    <i class="fas fa-trash"></i> Supprimer la Compagnie
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette compagnie d'assurance ?</p>
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
<style>
.small-box>.inner { padding: 10px; }
.small-box h3 { font-size: 38px; font-weight: bold; margin: 0 0 10px 0; }
.small-box .icon { position: absolute; top: -10px; right: 10px; font-size: 90px; color: rgba(0,0,0,0.15); }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    // Variables globales
    var table = null;
    
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
        table = $('#companiesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('assurance_companies.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        taux_min: $('#filter_taux_min').val(),
                        taux_max: $('#filter_taux_max').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nom', name: 'nom', orderable: true },
                { data: 'contact', name: 'contact', orderable: false },
                { data: 'couverture', name: 'taux_couverture', orderable: true },
                { data: 'delai', name: 'delai_remboursement', orderable: true },
                { data: 'assures', name: 'assures', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
            order: [[1, 'asc']],
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
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' compagnies'
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
    
    $('#filter_taux_min, #filter_taux_max').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_taux_min').val('');
        $('#filter_taux_max').val('');
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
            url: "{{ route('assurance_companies.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    $('#addForm')[0].reset();
                } else {
                    showToast(response.message || 'Erreur lors de la création', 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    
                    // Réinitialiser les erreurs
                    $('.invalid-feedback').remove();
                    $('.is-invalid').removeClass('is-invalid');
                    
                    // Afficher les erreurs
                    $.each(errors, function(key, value) {
                        let input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback" id="' + key + '_error">' + value[0] + '</div>');
                    });
                    
                    showToast('Veuillez corriger les erreurs dans le formulaire', 'warning');
                } else {
                    showToast('Une erreur est survenue', 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE ÉDITION ====================
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('assurance-companies') }}/" + id + "/edit",
            type: "GET",
            beforeSend: function() {
                $('#editModalContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Chargement...</div>');
                $('#editModal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    let company = response.data;
                    
                    let html = `
                    <form id="editForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="${company.id}">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="nom" class="form-control" required 
                                       value="${company.nom}" maxlength="100">
                                <div class="invalid-feedback" id="edit_nom_error"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Téléphone *</label>
                                        <input type="text" name="telephone" class="form-control" required 
                                               value="${company.telephone}" maxlength="20">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="${company.email || ''}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Taux de couverture (%) *</label>
                                        <input type="number" name="taux_couverture" class="form-control" required 
                                               min="0" max="100" step="0.01" value="${company.taux_couverture}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Délai de remboursement (jours) *</label>
                                        <input type="number" name="delai_remboursement" class="form-control" required 
                                               min="1" value="${company.delai_remboursement}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="submitEditBtn">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times"></i> Annuler
                            </button>
                        </div>
                    </form>`;
                    
                    $('#editModalContent').html(html);
                    
                    // Gérer la soumission du formulaire d'édition
                    $('#editForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        var submitBtn = $('#submitEditBtn');
                        var originalText = submitBtn.html();
                        
                        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mise à jour...');
                        
                        $.ajax({
                            url: "{{ url('assurance-companies') }}/" + id,
                            type: "POST",
                            data: $(this).serialize(),
                            headers: {
                                'X-HTTP-Method-Override': 'PUT'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#editModal').modal('hide');
                                    table.ajax.reload(null, false);
                                    showToast(response.message, 'success');
                                } else {
                                    showToast(response.message, 'error');
                                }
                                submitBtn.prop('disabled', false).html(originalText);
                            },
                            error: function(xhr) {
                                if (xhr.status === 422) {
                                    let errors = xhr.responseJSON.errors;
                                    
                                    // Afficher les erreurs
                                    $.each(errors, function(key, value) {
                                        $('[name="' + key + '"]').addClass('is-invalid')
                                            .after('<div class="invalid-feedback">' + value[0] + '</div>');
                                    });
                                    
                                    showToast('Veuillez corriger les erreurs', 'warning');
                                } else {
                                    showToast('Erreur lors de la mise à jour', 'error');
                                }
                                
                                submitBtn.prop('disabled', false).html(originalText);
                            }
                        });
                    });
                } else {
                    $('#editModalContent').html('<div class="alert alert-danger">Erreur: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#editModalContent').html('<div class="alert alert-danger">Erreur lors du chargement</div>');
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
            url: "{{ url('assurance-companies') }}/" + id,
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
            taux_min: $('#filter_taux_min').val(),
            taux_max: $('#filter_taux_max').val()
        };
        
        let queryString = $.param(filters);
        window.location.href = "{{ route('assurance_companies.export') }}?" + queryString;
    });
});
</script>
@stop