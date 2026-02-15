@extends('adminlte::page')

@section('title', 'Gestion des Coachs')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-user-tie"></i> Gestion des Coachs</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Coachs</li>
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
                    <h3>{{ $totalCoaches }}</h3>
                    <p>Total Coachs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalActifs }}</h3>
                    <p>Coachs Actifs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalInactifs }}</h3>
                    <p>Coachs Inactifs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($salaireMoyen, 2) }} DH</h3>
                    <p>Salaire Moyen</p>
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
                <h3 class="card-title">Liste des Coachs</h3>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nouveau Coach
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_search">Recherche</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_search" 
                                   placeholder="Nom, prénom, téléphone...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_statut">Statut</label>
                        <select class="form-control form-control-sm" id="filter_statut">
                            <option value="">Tous</option>
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_specialite">Spécialité</label>
                        <input type="text" class="form-control form-control-sm" id="filter_specialite" 
                               placeholder="Filtrer par spécialité">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="coachesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Nom & Prénom</th>
                        <th width="120">Spécialité</th>
                        <th width="150">Contact</th>
                        <th width="100">Salaire</th>
                        <th width="100">Activités</th>
                        <th width="80">Statut</th>
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

{{-- Modal Ajout Coach --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Nouveau Coach
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
                                <label for="nom">Nom *</label>
                                <input type="text" name="nom" id="nom" class="form-control" required 
                                       placeholder="Entrez le nom" maxlength="100">
                                <div class="invalid-feedback" id="nom_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" required 
                                       placeholder="Entrez le prénom" maxlength="100">
                                <div class="invalid-feedback" id="prenom_error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="specialite">Spécialité</label>
                                <input type="text" name="specialite" id="specialite" class="form-control" 
                                       placeholder="Ex: Fitness, Yoga, Musculation..." maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telephone">Téléphone *</label>
                                <input type="text" name="telephone" id="telephone" class="form-control" required 
                                       placeholder="Ex: 0612345678" maxlength="20">
                                <div class="invalid-feedback" id="telephone_error"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       placeholder="exemple@email.com">
                                <div class="invalid-feedback" id="email_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salaire">Salaire (DH)</label>
                                <input type="number" name="salaire" id="salaire" class="form-control" 
                                       min="0" step="0.01" placeholder="Salaire mensuel">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_embauche">Date d'embauche</label>
                                <input type="date" name="date_embauche" id="date_embauche" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="statut">Statut *</label>
                                <select name="statut" id="statut" class="form-control" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
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

{{-- Modal Voir Coach --}}
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Détails du Coach
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                    Chargement...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Éditer Coach --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Modifier le Coach
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
                    <i class="fas fa-trash"></i> Supprimer le Coach
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce coach ?</p>
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
        table = $('#coachesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('coaches.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        statut: $('#filter_statut').val(),
                        specialite: $('#filter_specialite').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nom_complet', name: 'nom_complet' },
                { data: 'specialite', name: 'specialite', orderable: false },
                { data: 'contact', name: 'contact', orderable: false },
                { data: 'salaire', name: 'salaire', orderable: true },
                { data: 'activites', name: 'activites', orderable: false, searchable: false },
                { data: 'statut_badge', name: 'statut', orderable: false, searchable: false },
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
                },
                aria: {
                    sortAscending:  ": activer pour trier la colonne par ordre croissant",
                    sortDescending: ": activer pour trier la colonne par ordre d&eacute;croissant"
                }
            },
            drawCallback: function(settings) {
                if (settings.json && settings.json.recordsTotal !== undefined) {
                    var api = this.api();
                    var pageInfo = api.page.info();
                    $('#tableInfo').text(
                        'Affichage de ' + (pageInfo.start + 1) + ' à ' + 
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' coachs'
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
    
    $('#filter_statut, #filter_specialite').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_statut').val('');
        $('#filter_specialite').val('');
        table.search('').draw();
    });
    
    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
        showToast('Table actualisée', 'success');
    });
    
    // ==================== MODALE AJOUT ====================
    $('#addModal').on('show.bs.modal', function() {
        $('#addForm')[0].reset();
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').empty();
    });
    
    // Soumission du formulaire d'ajout
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#submitAddBtn');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        
        $.ajax({
            url: "{{ route('coaches.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    submitBtn.prop('disabled', false).html(originalText);
                } else {
                    showToast(response.message || 'Erreur lors de la création', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    
                    // Réinitialiser les erreurs
                    $('.invalid-feedback').empty();
                    $('.is-invalid').removeClass('is-invalid');
                    
                    // Afficher les erreurs
                    $.each(errors, function(key, value) {
                        let input = $('[name="' + key + '"]');
                        let errorDiv = $('#' + key + '_error');
                        
                        if (input.length) {
                            input.addClass('is-invalid');
                            if (errorDiv.length) {
                                errorDiv.text(value[0]);
                            }
                        }
                    });
                    
                    showToast('Veuillez corriger les erreurs dans le formulaire', 'warning');
                } else {
                    showToast('Une erreur est survenue', 'error');
                }
                
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE VISION ====================
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('coaches') }}/" + id,
            type: "GET",
            beforeSend: function() {
                $('#viewModalContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Chargement...</div>');
                $('#viewModal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    let coach = response.data;
                    let html = `
                    <div class="row">
                        <div class="col-md-12">
                            <h4 class="text-center">${coach.nom} ${coach.prenom}</h4>
                            <hr>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Spécialité:</th>
                                    <td>${coach.specialite || '<span class="text-muted">N/A</span>'}</td>
                                </tr>
                                <tr>
                                    <th>Téléphone:</th>
                                    <td>${coach.telephone}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>${coach.email || '<span class="text-muted">N/A</span>'}</td>
                                </tr>
                                <tr>
                                    <th>Salaire:</th>
                                    <td>${coach.salaire ? coach.salaire + ' DH' : '<span class="text-muted">N/A</span>'}</td>
                                </tr>
                                <tr>
                                    <th>Date d'embauche:</th>
                                    <td>${coach.date_embauche || '<span class="text-muted">N/A</span>'}</td>
                                </tr>
                                <tr>
                                    <th>Statut:</th>
                                    <td>
                                        ${coach.statut == 'actif' ? 
                                            '<span class="badge badge-success">Actif</span>' : 
                                            '<span class="badge badge-danger">Inactif</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date création:</th>
                                    <td>${coach.created_at}</td>
                                </tr>
                            </table>
                        </div>
                    </div>`;
                    
                    $('#viewModalContent').html(html);
                } else {
                    $('#viewModalContent').html('<div class="alert alert-danger">Erreur: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#viewModalContent').html('<div class="alert alert-danger">Erreur lors du chargement des données</div>');
            }
        });
    });
    
    // ==================== MODALE ÉDITION ====================
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('coaches') }}/" + id + "/edit",
            type: "GET",
            beforeSend: function() {
                $('#editModalContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Chargement...</div>');
                $('#editModal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    let coach = response.data;
                    let html = `
                    <form id="editForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="${coach.id}">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nom *</label>
                                        <input type="text" name="nom" class="form-control" required 
                                               value="${coach.nom}" maxlength="100">
                                        <div class="invalid-feedback" id="edit_nom_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Prénom *</label>
                                        <input type="text" name="prenom" class="form-control" required 
                                               value="${coach.prenom}" maxlength="100">
                                        <div class="invalid-feedback" id="edit_prenom_error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Spécialité</label>
                                        <input type="text" name="specialite" class="form-control" 
                                               value="${coach.specialite || ''}" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Téléphone *</label>
                                        <input type="text" name="telephone" class="form-control" required 
                                               value="${coach.telephone}" maxlength="20">
                                        <div class="invalid-feedback" id="edit_telephone_error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="${coach.email || ''}">
                                        <div class="invalid-feedback" id="edit_email_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Salaire (DH)</label>
                                        <input type="number" name="salaire" class="form-control" 
                                               min="0" step="0.01" value="${coach.salaire || ''}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date d'embauche</label>
                                        <input type="date" name="date_embauche" class="form-control" 
                                               value="${coach.date_embauche || ''}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Statut *</label>
                                        <select name="statut" class="form-control" required>
                                            <option value="actif" ${coach.statut == 'actif' ? 'selected' : ''}>Actif</option>
                                            <option value="inactif" ${coach.statut == 'inactif' ? 'selected' : ''}>Inactif</option>
                                        </select>
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
                            url: "{{ url('coaches') }}/" + id,
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
                                        let input = $('#editForm [name="' + key + '"]');
                                        let errorDiv = $('#edit_' + key + '_error');
                                        
                                        if (input.length) {
                                            input.addClass('is-invalid');
                                            if (errorDiv.length) {
                                                errorDiv.text(value[0]);
                                            }
                                        }
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
            url: "{{ url('coaches') }}/" + id,
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
});
</script>
@stop