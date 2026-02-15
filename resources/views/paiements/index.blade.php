@extends('adminlte::page')

@section('title', 'Gestion des Paiements')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-money-bill-wave"></i> Gestion des Paiements</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Paiements</li>
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
                    <h3>{{ number_format($totalPaiements) }}</h3>
                    <p>Total Paiements</p>
                </div>
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($totalAujourdhui, 2) }} DH</h3>
                    <p>CA Aujourd'hui</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($totalMois, 2) }} DH</h3>
                    <p>CA Ce Mois</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totalAnnee, 2) }} DH</h3>
                    <p>CA Cette Année</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Paiements</h3>
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
                        <i class="fas fa-plus"></i> Nouveau Paiement
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
                                   placeholder="Nom, référence...">
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
                        <label for="filter_mode">Mode de paiement</label>
                        <select class="form-control form-control-sm" id="filter_mode">
                            <option value="">Tous</option>
                            <option value="especes">Espèces</option>
                            <option value="carte">Carte</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
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
                        <label for="filter_montant_min">Montant min</label>
                        <input type="number" class="form-control form-control-sm" id="filter_montant_min" 
                               placeholder="0" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_montant_max">Montant max</label>
                        <input type="number" class="form-control form-control-sm" id="filter_montant_max" 
                               placeholder="Max" min="0" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="paiementsTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Abonné</th>
                        <th width="120">Activité</th>
                        <th width="100">Montant</th>
                        <th width="100">Mode</th>
                        <th width="120">Référence</th>
                        <th width="120">Date</th>
                        <th width="100">Actions</th>
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

{{-- Modal Ajout Paiement --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouveau Paiement
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
                                <label>Abonnement *</label>
                                <select name="abonnement_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Sélectionner un abonnement</option>
                                    @foreach($abonnements as $abonnement)
                                        <option value="{{ $abonnement->id }}">
                                            {{ $abonnement->abonne->nom }} {{ $abonnement->abonne->prenom }} - 
                                            {{ $abonnement->activite->nom }} 
                                            ({{ $abonnement->type_abonnement }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant (DH) *</label>
                                <input type="number" name="montant" class="form-control" required 
                                       min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mode de paiement *</label>
                                <select name="mode_paiement" class="form-control" required>
                                    <option value="especes">Espèces</option>
                                    <option value="carte">Carte bancaire</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date de paiement *</label>
                                <input type="datetime-local" name="date_paiement" class="form-control" required 
                                       value="{{ date('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Référence</label>
                                <input type="text" name="reference" class="form-control" 
                                       placeholder="N° chèque, transaction...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>ID Imprimante</label>
                                <input type="text" name="imprimante_id" class="form-control" 
                                       placeholder="ID de l'imprimante">
                            </div>
                        </div>
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

{{-- Modal Éditer Paiement --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Modifier le Paiement
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
                    <i class="fas fa-trash"></i> Supprimer le Paiement
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce paiement ?</p>
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
        table = $('#paiementsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('paiements.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        mode: $('#filter_mode').val(),
                        date_debut: $('#filter_date_debut').val(),
                        date_fin: $('#filter_date_fin').val(),
                        montant_min: $('#filter_montant_min').val(),
                        montant_max: $('#filter_montant_max').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'abonne', name: 'abonne', orderable: false },
                { data: 'activite', name: 'activite', orderable: false },
                { data: 'montant', name: 'montant', orderable: true },
                { data: 'mode', name: 'mode_paiement', orderable: false },
                { data: 'reference', name: 'reference', orderable: false },
                { data: 'date', name: 'date_paiement', orderable: true },
                { data: 'action', orderable: false, searchable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
            order: [[6, 'desc']],
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
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' paiements'
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
    
    $('#filter_mode, #filter_date_debut, #filter_date_fin, #filter_montant_min, #filter_montant_max').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_mode').val('');
        $('#filter_date_debut').val('');
        $('#filter_date_fin').val('');
        $('#filter_montant_min').val('');
        $('#filter_montant_max').val('');
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
            url: "{{ route('paiements.store') }}",
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
    
    // ==================== MODALE ÉDITION ====================
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('paiements') }}/" + id + "/edit",
            type: "GET",
            beforeSend: function() {
                $('#editModalContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Chargement...</div>');
                $('#editModal').modal('show');
            },
            success: function(response) {
                if (response.success) {
                    let paiement = response.data;
                    
                    // Formater la date pour l'input datetime-local
                    let datePaiement = new Date(paiement.date_paiement);
                    let formattedDate = datePaiement.toISOString().slice(0, 16);
                    
                    let html = `
                    <form id="editForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="${paiement.id}">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Abonné: ${paiement.abonnement.abonne.nom} ${paiement.abonnement.abonne.prenom}<br>
                                Activité: ${paiement.abonnement.activite.nom}
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Montant (DH) *</label>
                                        <input type="number" name="montant" class="form-control" required 
                                               min="0" step="0.01" value="${paiement.montant}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mode de paiement *</label>
                                        <select name="mode_paiement" class="form-control" required>
                                            <option value="especes" ${paiement.mode_paiement == 'especes' ? 'selected' : ''}>Espèces</option>
                                            <option value="carte" ${paiement.mode_paiement == 'carte' ? 'selected' : ''}>Carte bancaire</option>
                                            <option value="cheque" ${paiement.mode_paiement == 'cheque' ? 'selected' : ''}>Chèque</option>
                                            <option value="virement" ${paiement.mode_paiement == 'virement' ? 'selected' : ''}>Virement</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date de paiement *</label>
                                        <input type="datetime-local" name="date_paiement" class="form-control" required 
                                               value="${formattedDate}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Référence</label>
                                        <input type="text" name="reference" class="form-control" 
                                               value="${paiement.reference || ''}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2">${paiement.notes || ''}</textarea>
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
                            url: "{{ url('paiements') }}/" + id,
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
            url: "{{ url('paiements') }}/" + id,
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
            mode: $('#filter_mode').val(),
            date_debut: $('#filter_date_debut').val(),
            date_fin: $('#filter_date_fin').val()
        };
        
        let queryString = $.param(filters);
        window.location.href = "{{ route('paiements.export') }}?" + queryString;
    });
});
</script>
@stop