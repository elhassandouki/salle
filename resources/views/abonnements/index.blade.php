@extends('adminlte::page')

@section('title', 'Gestion des Abonnements')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-id-card"></i> Gestion des Abonnements</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Abonnements</li>
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
                    <h3>{{ $totalAbonnements }}</h3>
                    <p>Total Abonnements</p>
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
                    <p>Abonnements Actifs</p>
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
                    <p>Abonnements Expirés</p>
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
                <h3 class="card-title">Liste des Abonnements</h3>
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
                        <i class="fas fa-plus"></i> Nouvel Abonnement
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
            <table id="abonnementsTable" class="table table-bordered table-striped table-hover w-100">
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
        
        <div class="card-footer">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                <span id="tableInfo">Chargement des données...</span>
            </small>
        </div>
    </div>
</div>

<!-- ==================== MODALES ==================== -->

{{-- Modal Ajout Abonnement --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouvel Abonnement
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
                                <label>Activité *</label>
                                <select name="activite_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Sélectionner une activité</option>
                                    @foreach($activites as $activite)
                                        <option value="{{ $activite->id }}">
                                            {{ $activite->nom }} 
                                            ({{ $activite->prix_mensuel }} DH/mois)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Type d'abonnement *</label>
                                <select name="type_abonnement" class="form-control" required id="type_abonnement">
                                    <option value="mensuel">Mensuel</option>
                                    <option value="trimestriel">Trimestriel</option>
                                    <option value="annuel">Annuel</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date début *</label>
                                <input type="date" name="date_debut" class="form-control" required 
                                       value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date fin</label>
                                <input type="date" name="date_fin" class="form-control" readonly 
                                       id="date_fin_calculee">
                                <small class="text-muted">Calculée automatiquement</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant (DH) *</label>
                                <input type="number" name="montant" class="form-control" required 
                                       min="0" step="0.01" id="montant_abonnement">
                                <small class="text-muted" id="montant_info"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Statut *</label>
                                <select name="statut" class="form-control" required>
                                    <option value="actif">Actif</option>
                                    <option value="suspendu">Suspendu</option>
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

{{-- Modal Paiement --}}
<div class="modal fade" id="paiementModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-money-bill-wave"></i> Nouveau Paiement
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="paiementForm">
                @csrf
                <input type="hidden" name="abonnement_id" id="paiement_abonnement_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Montant (DH) *</label>
                        <input type="number" name="montant" class="form-control" required 
                               min="0" step="0.01" id="paiement_montant">
                    </div>
                    <div class="form-group">
                        <label>Mode de paiement *</label>
                        <select name="mode_paiement" class="form-control" required>
                            <option value="especes">Espèces</option>
                            <option value="carte">Carte bancaire</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Référence</label>
                        <input type="text" name="reference" class="form-control" 
                               placeholder="N° chèque, transaction...">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="submitPaiementBtn">
                        <i class="fas fa-check"></i> Valider le paiement
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Changement Statut --}}
<div class="modal fade" id="statutModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt"></i> Changer le Statut
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="statutForm">
                @csrf
                <input type="hidden" name="abonnement_id" id="statut_abonnement_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nouveau statut *</label>
                        <select name="statut" class="form-control" required>
                            <option value="actif">Actif</option>
                            <option value="expiré">Expiré</option>
                            <option value="suspendu">Suspendu</option>
                        </select>
                    </div>
                    <div class="form-group" id="date_fin_group" style="display: none;">
                        <label>Nouvelle date de fin *</label>
                        <input type="date" name="date_fin" class="form-control" 
                               id="statut_date_fin">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning" id="submitStatutBtn">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Renouvellement --}}
<div class="modal fade" id="renouvelerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-redo"></i> Renouveler l'Abonnement
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Voulez-vous renouveler cet abonnement ?</p>
                <p class="text-muted">
                    <small>Un nouvel abonnement sera créé à partir du lendemain de la date de fin actuelle.</small>
                </p>
                <input type="hidden" id="renouveler_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" id="confirmRenouveler">
                    <i class="fas fa-redo"></i> Renouveler
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
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
                    <i class="fas fa-trash"></i> Supprimer l'Abonnement
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet abonnement ?</p>
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
        table = $('#abonnementsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('abonnements.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        statut: $('#filter_statut').val(),
                        activite_id: $('#filter_activite').val(),
                        type: $('#filter_type').val(),
                        date_debut: $('#filter_date_debut').val(),
                        date_fin: $('#filter_date_fin').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'abonne', name: 'abonne', orderable: false },
                { data: 'activite', name: 'activite', orderable: false },
                { data: 'type', name: 'type_abonnement', orderable: true },
                { data: 'dates', name: 'date_debut', orderable: true },
                { data: 'montant', name: 'montant', orderable: true },
                { data: 'jours_restants', name: 'jours_restants', orderable: false, searchable: false },
                { data: 'statut_badge', name: 'statut', orderable: false, searchable: false },
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
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' abonnements'
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
    
    $('#filter_statut, #filter_activite, #filter_type, #filter_date_debut, #filter_date_fin').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_statut').val('');
        $('#filter_activite').val('');
        $('#filter_type').val('');
        $('#filter_date_debut').val('');
        $('#filter_date_fin').val('');
        table.search('').draw();
    });
    
    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
        showToast('Table actualisée', 'success');
    });
    
    // ==================== MODALE AJOUT ====================
    // Calculer la date de fin automatiquement
    $('#type_abonnement, [name="date_debut"]').on('change', function() {
        let type = $('#type_abonnement').val();
        let dateDebut = $('[name="date_debut"]').val();
        
        if (dateDebut) {
            let date = new Date(dateDebut);
            let dateFin = new Date(date);
            
            switch(type) {
                case 'mensuel':
                    dateFin.setMonth(dateFin.getMonth() + 1);
                    break;
                case 'trimestriel':
                    dateFin.setMonth(dateFin.getMonth() + 3);
                    break;
                case 'annuel':
                    dateFin.setFullYear(dateFin.getFullYear() + 1);
                    break;
            }
            
            // Formater la date pour l'input
            let formattedDate = dateFin.toISOString().split('T')[0];
            $('#date_fin_calculee').val(formattedDate);
        }
    });
    
    // Calculer le montant selon l'activité
    $('[name="activite_id"]').on('change', function() {
        let activiteId = $(this).val();
        let type = $('#type_abonnement').val();
        
        if (activiteId) {
            $.ajax({
                url: "{{ url('activites') }}/" + activiteId + "/get-prix",
                type: "GET",
                success: function(response) {
                    if (response.prix && response.prix[type]) {
                        $('#montant_abonnement').val(response.prix[type]);
                        $('#montant_info').text('Prix ' + type + ' pour cette activité');
                    }
                }
            });
        }
    });
    
    // Soumission du formulaire d'ajout
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#submitAddBtn');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        
        $.ajax({
            url: "{{ route('abonnements.store') }}",
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
    
    // ==================== MODALE PAIEMENT ====================
    $(document).on('click', '.paiement-btn', function() {
        let id = $(this).data('id');
        $('#paiement_abonnement_id').val(id);
        $('#paiementModal').modal('show');
    });
    
    $('#paiementForm').on('submit', function(e) {
        e.preventDefault();
        let abonnementId = $('#paiement_abonnement_id').val();
        let submitBtn = $('#submitPaiementBtn');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');
        
        $.ajax({
            url: "{{ url('paiements') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#paiementModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    $('#paiementForm')[0].reset();
                } else {
                    showToast(response.message, 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                showToast('Erreur lors du paiement', 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE CHANGEMENT STATUT ====================
    $(document).on('click', '.statut-btn', function() {
        let id = $(this).data('id');
        $('#statut_abonnement_id').val(id);
        $('#statutModal').modal('show');
    });
    
    // Afficher/masquer le champ date de fin selon le statut
    $('#statutForm [name="statut"]').on('change', function() {
        if ($(this).val() === 'expiré') {
            $('#date_fin_group').show();
            $('#statut_date_fin').prop('required', true);
        } else {
            $('#date_fin_group').hide();
            $('#statut_date_fin').prop('required', false);
        }
    });
    
    $('#statutForm').on('submit', function(e) {
        e.preventDefault();
        let abonnementId = $('#statut_abonnement_id').val();
        let submitBtn = $('#submitStatutBtn');
        let originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mise à jour...');
        
        $.ajax({
            url: "{{ url('abonnements') }}/" + abonnementId + "/changer-statut",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#statutModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                showToast('Erreur lors du changement de statut', 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ==================== MODALE RENOUVELLEMENT ====================
    $(document).on('click', '.renouveler-btn', function() {
        let id = $(this).data('id');
        $('#renouveler_id').val(id);
        $('#renouvelerModal').modal('show');
    });
    
    $('#confirmRenouveler').on('click', function() {
        let id = $('#renouveler_id').val();
        let btn = $(this);
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Renouvellement...');
        
        $.ajax({
            url: "{{ url('abonnements') }}/" + id + "/renouveler",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#renouvelerModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                } else {
                    showToast(response.message, 'error');
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                showToast('Erreur lors du renouvellement', 'error');
                btn.prop('disabled', false).html(originalText);
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
            url: "{{ url('abonnements') }}/" + id,
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
            activite_id: $('#filter_activite').val(),
            type: $('#filter_type').val()
        };
        
        let queryString = $.param(filters);
        window.location.href = "{{ route('abonnements.export') }}?" + queryString;
    });
});
</script>
@stop