@extends('adminlte::page')

@section('title', 'Gestion des Abonnés')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Gestion des Abonnés</h1>
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#syncZkModal">
            <i class="fas fa-fingerprint"></i> Sync ZK F18
        </button>
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus"></i> Nouvel Abonné
        </button>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Statistiques -->
    <div class="row">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalAbonnes }}</h3>
                    <p>Total</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalHommes }}</h3>
                    <p>Hommes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-male"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalFemmes }}</h3>
                    <p>Femmes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-female"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalActifs }}</h3>
                    <p>Actifs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $totalInactifs }}</h3>
                    <p>Inactifs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totalExpireBientot }}</h3>
                    <p>Expire bientôt</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des Abonnés -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Abonnés</h3>
                <div>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-sm btn-primary" id="exportCSV">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Recherche</label>
                        <input type="text" class="form-control form-control-sm" id="filter_search" placeholder="Nom, Prénom, CIN, Téléphone...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Sexe</label>
                        <select class="form-control form-control-sm" id="filter_sexe">
                            <option value="">Tous</option>
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Statut</label>
                        <select class="form-control form-control-sm" id="filter_statut">
                            <option value="">Tous</option>
                            <option value="actif">Actifs</option>
                            <option value="inactif">Inactifs</option>
                            <option value="expire_bientot">Expire bientôt</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Type Abonnement</label>
                        <select class="form-control form-control-sm" id="filter_type_abonnement">
                            <option value="">Tous</option>
                            <option value="mensuel">Mensuel</option>
                            <option value="trimestriel">Trimestriel</option>
                            <option value="annuel">Annuel</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <table id="abonnesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="5%">Photo</th>
                        <th width="10%">Nom Complet</th>
                        <th width="8%">CIN</th>
                        <th width="8%">Carte N°</th>
                        <th width="8%">Téléphone</th>
                        <th width="10%">Email</th>
                        <th width="5%">Sexe</th>
                        <th width="8%">Date Naiss.</th>
                        <th width="5%">Âge</th>
                        <th width="5%">Statut</th>
                        <th width="8%">Abonnement</th>
                        <th width="8%">Date Fin</th>
                        <th width="12%">Actions</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <!-- Les données seront chargées via AJAX -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="10" class="text-right">Total Abonnés Actifs :</th>
                        <th id="total_actifs">0</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Pagination simple -->
            <div class="row mt-3">
                <div class="col-sm-12 col-md-5">
                    <div class="dataTables_info" id="table_info" role="status" aria-live="polite">
                        Affichage de 0 à 0 sur 0 éléments
                    </div>
                </div>
                <div class="col-sm-12 col-md-7">
                    <div class="dataTables_paginate paging_simple_numbers" id="table_paginate">
                        <ul class="pagination" id="pagination"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AJOUT -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouvel Abonné</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="form-group">
                                <label>Photo</label>
                                <div class="text-center mb-3">
                                    <img id="photoPreview" src="https://ui-avatars.com/api/?name=Photo&background=28a745&color=fff&size=120" 
                                         class="img-circle elevation-2" style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <div class="form-group">
                                    <input type="file" name="photo" id="photo" class="form-control-file" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nom <span class="text-danger">*</span></label>
                                        <input type="text" name="nom" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Prénom <span class="text-danger">*</span></label>
                                        <input type="text" name="prenom" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>CIN</label>
                                        <input type="text" name="cin" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Carte N°</label>
                                        <input type="text" name="card_id" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Téléphone <span class="text-danger">*</span></label>
                                        <input type="text" name="telephone" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Sexe</label>
                                        <select name="sexe" class="form-control">
                                            <option value="">Non spécifié</option>
                                            <option value="Homme">Homme</option>
                                            <option value="Femme">Femme</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date Naissance</label>
                                        <input type="date" name="date_naissance" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Lieu Naissance</label>
                                        <input type="text" name="lieu_naissance" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nationalité</label>
                                        <input type="text" name="nationalite" class="form-control" value="Marocaine">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Situation Fam.</label>
                                        <select name="situation_familiale" class="form-control">
                                            <option value="">Sélectionner</option>
                                            <option value="Célibataire">Célibataire</option>
                                            <option value="Marié(e)">Marié(e)</option>
                                            <option value="Divorcé(e)">Divorcé(e)</option>
                                            <option value="Veuf(ve)">Veuf(ve)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Profession</label>
                                        <input type="text" name="profession" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <textarea name="adresse" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL VOIR -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Détails Abonné</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ÉDITER -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier Abonné</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div id="editModalContent">
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ABONNEMENT -->
<div class="modal fade" id="abonnementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Gérer Abonnement</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="abonnementForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="abonne_id" id="abonnement_abonne_id">
                    <div class="form-group">
                        <label>Type d'abonnement</label>
                        <select name="type_abonnement" class="form-control" required>
                            <option value="mensuel">Mensuel</option>
                            <option value="trimestriel">Trimestriel</option>
                            <option value="annuel">Annuel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="date_debut" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Montant</label>
                        <input type="number" name="montant" class="form-control" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Ajouter abonnement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Supprimer Abonné</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet abonné ?</p>
                <p class="text-danger"><strong>Cette action est irréversible !</strong></p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
    </div>

<!-- MODAL SYNC ZK -->
<div class="modal fade" id="syncZkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-fingerprint"></i> Synchroniser vers ZK F18</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="syncZkForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Quels abonnés voulez-vous envoyer vers la machine ?</label>
                        <select name="sync_scope" class="form-control" required>
                            <option value="actifs">Seulement les abonnés actifs / qui ont payé</option>
                            <option value="all">Tous les abonnés</option>
                        </select>
                    </div>
                    <div class="alert alert-info mb-0">
                        Les abonnés sans <code>uid</code> recevront automatiquement un UID basé sur leur ID avant l'envoi vers ZKTeco.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Lancer la synchronisation</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
.small-box {
    border-radius: 0.25rem;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
}
.small-box>.inner {
    padding: 10px;
}
.small-box h3 {
    font-size: 32px;
    font-weight: bold;
    margin: 0 0 10px 0;
}
.small-box .icon {
    position: absolute;
    top: -10px;
    right: 10px;
    font-size: 70px;
    color: rgba(0,0,0,0.15);
}
.btn-group .btn {
    margin-right: 3px;
}
.table td {
    vertical-align: middle;
}
.badge {
    font-size: 85%;
    padding: 5px 8px;
}
tfoot th {
    font-weight: bold;
    background-color: #f4f6f9;
}
.modal-header .close {
    color: white;
    opacity: 1;
}
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
// Configuration Toastr
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000"
};

$(document).ready(function() {
    // CSRF Token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Variables de pagination
    var currentPage = 1;
    var pageSize = 10;
    var totalRecords = 0;

    // Charger les données
    function loadData(page = 1) {
        currentPage = page;
        
        $.ajax({
            url: "{{ route('abonnes.getData') }}",
            type: "GET",
            data: {
                draw: page,
                start: (page - 1) * pageSize,
                length: pageSize,
                filters: {
                    search: $('#filter_search').val(),
                    sexe: $('#filter_sexe').val(),
                    statut: $('#filter_statut').val(),
                    type_abonnement: $('#filter_type_abonnement').val()
                }
            },
            success: function(response) {
                console.log("Données reçues:", response);
                
                if (response.data) {
                    displayData(response.data);
                    totalRecords = response.recordsTotal || 0;
                    updatePagination();
                    updateInfo();
                    
                    // Compter les actifs
                    var actifs = response.data.filter(function(item) {
                        return item.statut_badge && item.statut_badge.indexOf('Actif') > -1;
                    }).length;
                    $('#total_actifs').text(actifs + ' actifs');
                }
            },
            error: function(xhr) {
                console.error("Erreur AJAX:", xhr.responseText);
                toastr.error("Erreur de chargement des données");
            }
        });
    }

    // Afficher les données dans le tableau
    function displayData(data) {
        var html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="14" class="text-center">Aucune donnée disponible</td></tr>';
        } else {
            $.each(data, function(index, item) {
                html += '<tr>';
                html += '<td>' + item.DT_RowIndex + '</td>';
                html += '<td class="text-center">' + (item.photo || '-') + '</td>';
                html += '<td>' + (item.nom_complet || '-') + '</td>';
                html += '<td>' + (item.cin || '-') + '</td>';
                html += '<td>' + (item.card_id || '-') + '</td>';
                html += '<td>' + (item.telephone || '-') + '</td>';
                html += '<td>' + (item.email || '-') + '</td>';
                html += '<td class="text-center">' + (item.sexe || '-') + '</td>';
                html += '<td class="text-center">' + (item.date_naissance || '-') + '</td>';
                html += '<td class="text-center">' + (item.age || '-') + '</td>';
                html += '<td class="text-center">' + (item.statut_badge || '-') + '</td>';
                html += '<td class="text-center">' + (item.type_abonnement || '-') + '</td>';
                html += '<td class="text-center">' + (item.expiration_badge || '-') + '</td>';
                html += '<td class="text-center">' + (item.action || '-') + '</td>';
                html += '</tr>';
            });
        }
        
        $('#table-body').html(html);
    }

    // Mettre à jour la pagination
    function updatePagination() {
        var totalPages = Math.ceil(totalRecords / pageSize);
        var html = '';
        
        html += '<li class="paginate_button page-item previous ' + (currentPage === 1 ? 'disabled' : '') + '">';
        html += '<a href="#" class="page-link" data-page="' + (currentPage - 1) + '">Précédent</a>';
        html += '</li>';
        
        for (var i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += '<li class="paginate_button page-item ' + (i === currentPage ? 'active' : '') + '">';
                html += '<a href="#" class="page-link" data-page="' + i + '">' + i + '</a>';
                html += '</li>';
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        html += '<li class="paginate_button page-item next ' + (currentPage === totalPages ? 'disabled' : '') + '">';
        html += '<a href="#" class="page-link" data-page="' + (currentPage + 1) + '">Suivant</a>';
        html += '</li>';
        
        $('#pagination').html(html);
    }

    // Mettre à jour les informations
    function updateInfo() {
        var start = (currentPage - 1) * pageSize + 1;
        var end = Math.min(currentPage * pageSize, totalRecords);
        
        $('#table_info').text('Affichage de ' + start + ' à ' + end + ' sur ' + totalRecords + ' éléments');
    }

    // Gestionnaire de clic sur la pagination
    $(document).on('click', '#pagination a.page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page && !$(this).parent().hasClass('disabled')) {
            loadData(page);
        }
    });

    // Charger les données au chargement de la page
    loadData(1);

    // Filtres
    $('#filter_search').on('keyup', debounce(function() { 
        loadData(1); 
    }, 500));
    
    $('#filter_sexe, #filter_statut, #filter_type_abonnement').on('change', function() { 
        loadData(1); 
    });

    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_sexe').val('');
        $('#filter_statut').val('');
        $('#filter_type_abonnement').val('');
        loadData(1);
        toastr.info('Filtres réinitialisés');
    });

    $('#refreshTable').on('click', function() {
        loadData(currentPage);
        toastr.info('Table actualisée');
    });

    $('#exportCSV').on('click', function() {
        window.location.href = "{{ route('abonnes.export') }}";
    });

    $('#syncZkForm').on('submit', function(e) {
        e.preventDefault();

        let form = $(this);
        let btn = form.find('button[type="submit"]');
        let originalText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Synchronisation...');

        $.ajax({
            url: "{{ route('abonnes.sync-all-zk') }}",
            type: "POST",
            data: form.serialize(),
            success: function(response) {
                $('#syncZkModal').modal('hide');
                if (response.success) {
                    toastr.success(response.message || 'Synchronisation terminée');
                } else {
                    toastr.warning(response.message || 'La synchronisation n\'a pas abouti');
                }
                form[0].reset();
            },
            error: function(xhr) {
                let message = 'Erreur lors de la synchronisation avec ZKTeco';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                toastr.error(message);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Fonction debounce
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ==================== PHOTO PREVIEW ====================
    $('#photo').on('change', function() {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#photoPreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // ==================== MODAL AJOUT ====================
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let btn = $(this).find('button[type="submit"]');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        
        $.ajax({
            url: "{{ route('abonnes.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addModal').modal('hide');
                    loadData(1);
                    toastr.success(response.message);
                    
                    // Reset form
                    $('#addForm')[0].reset();
                    $('#photoPreview').attr('src', 'https://ui-avatars.com/api/?name=Photo&background=28a745&color=fff&size=120');
                } else {
                    toastr.error(response.message);
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').remove();
                    
                    $.each(errors, function(key, value) {
                        let input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                    toastr.warning('Veuillez corriger les erreurs');
                } else {
                    toastr.error('Une erreur est survenue');
                }
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== MODAL VOIR ====================
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        $('#viewModalContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...</div>');
        $('#viewModal').modal('show');
        
        $.ajax({
            url: "{{ url('abonnes') }}/" + id,
            type: "GET",
            success: function(response) {
                if (response.success) {
                    let a = response.data.abonne;
                    let abonnements = response.data.abonnements || [];
                    let photoUrl = a.photo ? '/storage/' + a.photo : 'https://ui-avatars.com/api/?name=' + a.nom + '+' + a.prenom + '&background=17a2b8&color=fff&size=150';
                    
                    let html = '<div class="row">';
                    html += '<div class="col-md-4 text-center">';
                    html += '<img src="' + photoUrl + '" class="img-circle elevation-2 mb-3" style="width:150px;height:150px;object-fit:cover;">';
                    html += '<h4>' + a.nom + ' ' + a.prenom + '</h4>';
                    html += '<span class="badge badge-' + (response.data.est_actif ? 'success' : 'secondary') + ' p-2">' + (response.data.est_actif ? 'Actif' : 'Inactif') + '</span>';
                    html += '</div>';
                    html += '<div class="col-md-8">';
                    html += '<table class="table table-bordered table-striped">';
                    html += '<tr><th>CIN:</th><td>' + (a.cin || '-') + '</td></tr>';
                    html += '<tr><th>Carte N°:</th><td>' + (a.card_id || '-') + '</td></tr>';
                    html += '<tr><th>Téléphone:</th><td>' + a.telephone + '</td></tr>';
                    html += '<tr><th>Email:</th><td>' + (a.email || '-') + '</td></tr>';
                    html += '<tr><th>Sexe:</th><td>' + (a.sexe || '-') + '</td></tr>';
                    html += '<tr><th>Date Naissance:</th><td>' + (a.date_naissance || '-') + '</td></tr>';
                    html += '<tr><th>Âge:</th><td>' + (response.data.age || '-') + ' ans</td></tr>';
                    html += '<tr><th>Adresse:</th><td>' + (a.adresse || '-') + '</td></tr>';
                    html += '</table>';
                    
                    if (abonnements.length > 0) {
                        html += '<h5 class="mt-3">Historique des abonnements</h5>';
                        html += '<table class="table table-sm table-bordered">';
                        html += '<thead><tr><th>Type</th><th>Début</th><th>Fin</th><th>Montant</th><th>Statut</th></tr></thead>';
                        html += '<tbody>';
                        abonnements.forEach(function(ab) {
                            html += '<tr>';
                            html += '<td>' + (ab.type_abonnement || '-') + '</td>';
                            html += '<td>' + (ab.date_debut || '-') + '</td>';
                            html += '<td>' + (ab.date_fin || '-') + '</td>';
                            html += '<td>' + (ab.montant || '-') + ' DH</td>';
                            html += '<td><span class="badge badge-' + (ab.statut === 'actif' ? 'success' : 'secondary') + '">' + (ab.statut || '-') + '</span></td>';
                            html += '</tr>';
                        });
                        html += '</tbody>';
                        html += '</table>';
                    }
                    
                    html += '</div></div>';
                    
                    $('#viewModalContent').html(html);
                }
            },
            error: function() {
                $('#viewModalContent').html('<div class="alert alert-danger">Erreur de chargement des données</div>');
            }
        });
    });

    // ==================== MODAL ÉDITER ====================
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        $('#editModalContent').html('<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i> Chargement...</div>');
        $('#editModal').modal('show');
        
        $.ajax({
            url: "{{ url('abonnes') }}/" + id + "/edit",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    let a = response.abonne;
                    let photoUrl = a.photo ? '/storage/' + a.photo : 'https://ui-avatars.com/api/?name=' + a.nom + '+' + a.prenom + '&background=ffc107&color=000&size=120';
                    
                    let html = `
                        <form id="editForm" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" name="id" value="` + a.id + `">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img id="editPhotoPreview" src="` + photoUrl + `" 
                                             class="img-circle elevation-2 mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                                        <div class="form-group">
                                            <input type="file" name="photo" id="edit_photo" class="form-control-file" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nom</label>
                                                    <input type="text" name="nom" class="form-control" value="` + a.nom + `" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Prénom</label>
                                                    <input type="text" name="prenom" class="form-control" value="` + a.prenom + `" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>CIN</label>
                                                    <input type="text" name="cin" class="form-control" value="` + (a.cin || '') + `">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Carte N°</label>
                                                    <input type="text" name="card_id" class="form-control" value="` + (a.card_id || '') + `">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Téléphone</label>
                                                    <input type="text" name="telephone" class="form-control" value="` + a.telephone + `" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control" value="` + (a.email || '') + `">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Sexe</label>
                                                    <select name="sexe" class="form-control">
                                                        <option value="">Non spécifié</option>
                                                        <option value="Homme" ` + (a.sexe === 'Homme' ? 'selected' : '') + `>Homme</option>
                                                        <option value="Femme" ` + (a.sexe === 'Femme' ? 'selected' : '') + `>Femme</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Date Naiss.</label>
                                                    <input type="date" name="date_naissance" class="form-control" value="` + (a.date_naissance || '') + `">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Lieu</label>
                                                    <input type="text" name="lieu_naissance" class="form-control" value="` + (a.lieu_naissance || '') + `">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Nationalité</label>
                                                    <input type="text" name="nationalite" class="form-control" value="` + (a.nationalite || 'Marocaine') + `">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Situation</label>
                                                    <select name="situation_familiale" class="form-control">
                                                        <option value="">Choisir</option>
                                                        <option value="Célibataire" ` + (a.situation_familiale === 'Célibataire' ? 'selected' : '') + `>Célibataire</option>
                                                        <option value="Marié(e)" ` + (a.situation_familiale === 'Marié(e)' ? 'selected' : '') + `>Marié(e)</option>
                                                        <option value="Divorcé(e)" ` + (a.situation_familiale === 'Divorcé(e)' ? 'selected' : '') + `>Divorcé(e)</option>
                                                        <option value="Veuf(ve)" ` + (a.situation_familiale === 'Veuf(ve)' ? 'selected' : '') + `>Veuf(ve)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Profession</label>
                                                    <input type="text" name="profession" class="form-control" value="` + (a.profession || '') + `">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Adresse</label>
                                            <textarea name="adresse" class="form-control" rows="2">` + (a.adresse || '') + `</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" class="form-control" rows="2">` + (a.notes || '') + `</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                            </div>
                        </form>
                    `;
                    
                    $('#editModalContent').html(html);
                    
                    $('#edit_photo').on('change', function() {
                        let file = this.files[0];
                        if (file) {
                            let reader = new FileReader();
                            reader.onload = function(e) {
                                $('#editPhotoPreview').attr('src', e.target.result);
                            }
                            reader.readAsDataURL(file);
                        }
                    });
                    
                    $('#editForm').on('submit', function(e) {
                        e.preventDefault();
                        let formData = new FormData(this);
                        
                        $.ajax({
                            url: "{{ url('abonnes') }}/" + id,
                            type: "POST",
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {'X-HTTP-Method-Override': 'PUT'},
                            success: function(response) {
                                if (response.success) {
                                    $('#editModal').modal('hide');
                                    loadData(currentPage);
                                    toastr.success(response.message);
                                }
                            },
                            error: function(xhr) {
                                if (xhr.status === 422) {
                                    let errors = xhr.responseJSON.errors;
                                    $('.is-invalid').removeClass('is-invalid');
                                    $('.invalid-feedback').remove();
                                    
                                    $.each(errors, function(key, value) {
                                        let input = $('#editForm [name="' + key + '"]');
                                        input.addClass('is-invalid');
                                        input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                                    });
                                    toastr.warning('Veuillez corriger les erreurs');
                                } else {
                                    toastr.error('Erreur lors de la mise à jour');
                                }
                            }
                        });
                    });
                }
            }
        });
    });

    // ==================== MODAL ABONNEMENT ====================
    $(document).on('click', '.abonnement-btn', function() {
        let id = $(this).data('id');
        $('#abonnement_abonne_id').val(id);
        $('#abonnementModal').modal('show');
    });

    $('#abonnementForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#abonnement_abonne_id').val();
        let btn = $(this).find('button[type="submit"]');
        let originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ajout...');
        
        $.ajax({
            url: "{{ url('abonnes') }}/" + id + "/abonnement",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#abonnementModal').modal('hide');
                    loadData(currentPage);
                    toastr.success(response.message);
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                toastr.error('Erreur lors de l\'ajout');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== SUPPRESSION ====================
    $(document).on('click', '.delete-btn', function() {
        $('#delete_id').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        let id = $('#delete_id').val();
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');
        
        $.ajax({
            url: "{{ url('abonnes') }}/" + id,
            type: "DELETE",
            data: {_token: "{{ csrf_token() }}"},
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    loadData(currentPage);
                    toastr.success(response.message);
                }
                btn.prop('disabled', false).html('Supprimer');
            },
            error: function() {
                toastr.error('Erreur lors de la suppression');
                btn.prop('disabled', false).html('Supprimer');
            }
        });
    });
});
</script>
@stop

@section('plugins.Datatables', true)
@section('plugins.Toastr', true)
@section('plugins.BsCustomFileInput', true)
