@extends('adminlte::page')

@section('title', 'Gestion des Pointages')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-door-open"></i> Gestion des Pointages</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Pointages</li>
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
                    <h3>{{ number_format($totalPointages) }}</h3>
                    <p>Total Pointages</p>
                </div>
                <div class="icon">
                    <i class="fas fa-history"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $pointagesAujourdhui }}</h3>
                    <p>Pointages Aujourd'hui</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $entreesAujourdhui }}</h3>
                    <p>Entrées Aujourd'hui</p>
                </div>
                <div class="icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $sortiesAujourdhui }}</h3>
                    <p>Sorties Aujourd'hui</p>
                </div>
                <div class="icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Pointages</h3>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-success" id="importZKBtn" data-toggle="modal" data-target="#importModal">
                        <i class="fas fa-upload"></i> Import ZK
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nouveau Pointage
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
                                   placeholder="Nom, UID...">
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
                        <label for="filter_type">Type</label>
                        <select class="form-control form-control-sm" id="filter_type">
                            <option value="">Tous</option>
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_date">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date">
                    </div>
                </div>
                <div class="col-md-2">
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
                        <label for="filter_synced">Synchronisation</label>
                        <select class="form-control form-control-sm" id="filter_synced">
                            <option value="">Tous</option>
                            <option value="1">Synchronisés</option>
                            <option value="0">Non synchronisés</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="filter_export">&nbsp;</label>
                        <button class="btn btn-sm btn-warning btn-block" id="exportBtn">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="pointagesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Abonné</th>
                        <th width="100">UID</th>
                        <th width="120">Date & Heure</th>
                        <th width="80">Type</th>
                        <th width="100">Synchronisation</th>
                        <th width="80">Actions</th>
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

{{-- Modal Ajout Pointage --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouveau Pointage
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Abonné *</label>
                        <select name="abonne_id" class="form-control select2" required style="width: 100%;">
                            <option value="">Sélectionner un abonné</option>
                            @foreach($abonnes as $abonne)
                                <option value="{{ $abonne->id }}">
                                    {{ $abonne->nom }} {{ $abonne->prenom }} 
                                    ({{ $abonne->uid ?? 'N/C' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Seuls les abonnés avec UID et abonnement actif</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date et heure *</label>
                                <input type="datetime-local" name="date_pointage" class="form-control" required 
                                       value="{{ date('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="entree">Entrée</option>
                                    <option value="sortie">Sortie</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>UID (optionnel)</label>
                        <input type="text" name="uid" class="form-control" 
                               placeholder="UID ZKTeco">
                        <small class="text-muted">Laisser vide pour utiliser l'UID de l'abonné</small>
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

{{-- Modal Import ZKTeco --}}
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-upload"></i> Import depuis ZKTeco
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Instructions :</strong><br>
                    1. Exportez les données de pointage depuis ZKTeco au format CSV<br>
                    2. Assurez-vous que les colonnes sont : UID, Date, Heure, Type<br>
                    3. Téléchargez le fichier CSV ci-dessous
                </div>
                
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Fichier CSV *</label>
                        <div class="custom-file">
                            <input type="file" name="csv_file" class="custom-file-input" id="csvFile" 
                                   accept=".csv,.txt" required>
                            <label class="custom-file-label" for="csvFile">Choisir un fichier</label>
                        </div>
                        <small class="text-muted">Format attendu : UID,YYYY-MM-DD HH:MM:SS,TYPE</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Options d'import</label>
                        <div class="form-check">
                            <input type="checkbox" name="ignore_errors" class="form-check-input" id="ignoreErrors">
                            <label class="form-check-label" for="ignoreErrors">
                                Ignorer les erreurs et continuer
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="mark_synced" class="form-check-input" id="markSynced" checked>
                            <label class="form-check-label" for="markSynced">
                                Marquer comme synchronisé
                            </label>
                        </div>
                    </div>
                    
                    <div id="importPreview" class="mt-3" style="display: none;">
                        <h6>Aperçu des données :</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="previewTable">
                                <thead>
                                    <tr>
                                        <th>UID</th>
                                        <th>Date</th>
                                        <th>Heure</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="importStats" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-success" id="submitImportBtn" disabled>
                        <i class="fas fa-upload"></i> Importer
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
                    <i class="fas fa-trash"></i> Supprimer le Pointage
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce pointage ?</p>
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
#previewTable { font-size: 12px; }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
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
        table = $('#pointagesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('pointages.getData') }}",
                data: function(d) {
                    d.filters = {
                        search: $('#filter_search').val(),
                        type: $('#filter_type').val(),
                        date: $('#filter_date').val(),
                        abonne_id: $('#filter_abonne').val(),
                        synced: $('#filter_synced').val()
                    };
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'abonne', name: 'abonne', orderable: false },
                { data: 'uid', name: 'uid', orderable: false },
                { data: 'date', name: 'date_pointage', orderable: true },
                { data: 'type', name: 'type', orderable: false },
                { data: 'synced_badge', name: 'synced', orderable: false },
                { data: 'action', orderable: false, searchable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
            order: [[3, 'desc']],
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
                        (pageInfo.end) + ' sur ' + pageInfo.recordsTotal + ' pointages'
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
    
    $('#filter_type, #filter_date, #filter_abonne, #filter_synced').on('change', function() {
        table.draw();
    });
    
    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_type').val('');
        $('#filter_date').val('');
        $('#filter_abonne').val('');
        $('#filter_synced').val('');
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
            url: "{{ route('pointages.store') }}",
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
    
    // ==================== MODALE IMPORT ZKTECO ====================
    // Afficher le nom du fichier CSV
    $('#csvFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName);
        
        if (fileName) {
            previewCSV(this);
        }
    });
    
    // Prévisualiser le fichier CSV
    function previewCSV(input) {
        var file = input.files[0];
        
        Papa.parse(file, {
            header: false,
            skipEmptyLines: true,
            complete: function(results) {
                var data = results.data;
                var valid = 0;
                var invalid = 0;
                var previewHtml = '';
                
                // Limiter l'aperçu aux 10 premières lignes
                var previewData = data.slice(0, 10);
                
                previewData.forEach(function(row, index) {
                    var isValid = row.length >= 3;
                    var status = isValid ? 
                        '<span class="badge badge-success">OK</span>' : 
                        '<span class="badge badge-danger">Erreur format</span>';
                    
                    previewHtml += `
                    <tr>
                        <td>${row[0] || ''}</td>
                        <td>${row[1] || ''}</td>
                        <td>${row[2] || ''}</td>
                        <td>${row[3] || ''}</td>
                        <td>${status}</td>
                    </tr>`;
                    
                    if (isValid) valid++; else invalid++;
                });
                
                $('#previewTable tbody').html(previewHtml);
                $('#importStats').html(`
                    <div class="alert alert-info">
                        <strong>Statistiques :</strong><br>
                        Lignes valides : ${valid}<br>
                        Lignes invalides : ${invalid}<br>
                        Total lignes : ${data.length}
                    </div>
                `);
                $('#importPreview').show();
                
                // Activer le bouton d'import si au moins une ligne valide
                $('#submitImportBtn').prop('disabled', valid === 0);
            },
            error: function(error) {
                showToast('Erreur lors de la lecture du fichier', 'error');
            }
        });
    }
    
    // Soumission du formulaire d'import
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitBtn = $('#submitImportBtn');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Import...');
        
        $.ajax({
            url: "{{ route('pointages.import-zk') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#importModal').modal('hide');
                    table.ajax.reload(null, false);
                    
                    var message = 'Import terminé : ' + response.imported + ' pointages importés';
                    if (response.failed > 0) {
                        message += ', ' + response.failed + ' échecs';
                    }
                    
                    showToast(message, 'success');
                    
                    // Afficher les erreurs détaillées si présentes
                    if (response.errors && response.errors.length > 0) {
                        console.log('Erreurs d\'import:', response.errors);
                    }
                    
                    $('#importForm')[0].reset();
                    $('#importPreview').hide();
                } else {
                    showToast(response.message, 'error');
                }
                submitBtn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showToast('Format de fichier invalide', 'warning');
                } else {
                    showToast('Erreur lors de l\'import', 'error');
                }
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
            url: "{{ url('pointages') }}/" + id,
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
            type: $('#filter_type').val(),
            date: $('#filter_date').val(),
            abonne_id: $('#filter_abonne').val(),
            synced: $('#filter_synced').val()
        };
        
        let queryString = $.param(filters);
        window.location.href = "{{ route('pointages.export') }}?" + queryString;
    });
});
</script>
@stop