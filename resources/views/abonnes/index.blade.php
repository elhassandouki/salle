@extends('adminlte::page')

@section('title', 'Gestion des Abonnés')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-users"></i> Gestion des Abonnés</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Abonnés</li>
            </ol>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Messages Flash -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h5><i class="icon fas fa-check"></i> Succès!</h5>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h5><i class="icon fas fa-ban"></i> Erreur!</h5>
            {{ session('error') }}
        </div>
    @endif

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
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Liste des Abonnés
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus"></i> Nouvel Abonné
                </button>
                <button type="button" class="btn btn-sm btn-info" id="refreshTable">
                    <i class="fas fa-sync"></i> Actualiser
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="exportCSV">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtres -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Recherche:</label>
                        <input type="text" class="form-control" id="filter_search" placeholder="Nom, Prénom, CIN, Téléphone...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Sexe:</label>
                        <select class="form-control" id="filter_sexe">
                            <option value="">Tous</option>
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Statut:</label>
                        <select class="form-control" id="filter_statut">
                            <option value="">Tous</option>
                            <option value="actif">Actifs</option>
                            <option value="inactif">Inactifs</option>
                            <option value="expire_bientot">Expire bientôt</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-block btn-default" id="resetFilters">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <table id="abonnesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th width="50">Photo</th>
                        <th>Nom Complet</th>
                        <th>CIN</th>
                        <th>Carte N°</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Sexe</th>
                        <th>Date Naiss.</th>
                        <th>Âge</th>
                        <th>Statut</th>
                        <th>Abonnement</th>
                        <th>Date Fin</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==================== MODAL AJOUT ==================== -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Nouvel Abonné</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="form-group">
                                <label>Photo</label>
                                <div class="text-center mb-3">
                                    <img id="photoPreview" src="https://ui-avatars.com/api/?name=Photo&background=0D6EFD&color=fff&size=120" 
                                         class="img-circle elevation-2" style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <div class="custom-file">
                                    <input type="file" name="photo" id="photo" class="custom-file-input" accept="image/*">
                                    <label class="custom-file-label" for="photo">Choisir photo</label>
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
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL ABONNEMENT ==================== -->
<div class="modal fade" id="abonnementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">Gérer Abonnement</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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

<!-- ==================== MODAL VOIR ==================== -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Détails Abonné</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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

<!-- ==================== MODAL ÉDITER ==================== -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Modifier Abonné</h5>
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

<!-- ==================== MODAL SUPPRESSION ==================== -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Supprimer Abonné</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet abonné ?</p>
                <p class="text-danger"><strong>Cette action est irréversible !</strong></p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
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
    // ==================== DATATABLE ====================
    var table = $('#abonnesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('abonnes.getData') }}",
            data: function(d) {
                d.filters = {
                    search: $('#filter_search').val(),
                    sexe: $('#filter_sexe').val(),
                    statut: $('#filter_statut').val()
                };
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'photo', name: 'photo', orderable: false, searchable: false },
            { data: 'nom_complet', name: 'nom_complet' },
            { data: 'cin', name: 'cin' },
            { data: 'card_id', name: 'card_id' },
            { data: 'telephone', name: 'telephone' },
            { data: 'email', name: 'email' },
            { data: 'sexe', name: 'sexe' },
            { data: 'date_naissance', name: 'date_naissance' },
            { data: 'age', name: 'age' },
            { data: 'statut_badge', name: 'statut_badge', orderable: false },
            { data: 'type_abonnement', name: 'type_abonnement' },
            { data: 'expiration_badge', name: 'expiration_badge', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        pageLength: 10,
        language: {
            processing: "Traitement...",
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ éléments",
            info: "Affichage _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Affichage 0 à 0 sur 0 éléments",
            infoFiltered: "(filtré de _MAX_ éléments)",
            zeroRecords: "Aucun résultat",
            paginate: {
                first: "Premier",
                previous: "Précédent",
                next: "Suivant",
                last: "Dernier"
            }
        }
    });

    // Filtres
    $('#filter_search').on('keyup', function() { table.draw(); });
    $('#filter_sexe, #filter_statut').on('change', function() { table.draw(); });

    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_sexe').val('');
        $('#filter_statut').val('');
        table.draw();
    });

    $('#refreshTable').on('click', function() {
        table.ajax.reload();
        toastr.info('Table actualisée');
    });

    $('#exportCSV').on('click', function() {
        window.location.href = "{{ route('abonnes.export') }}";
    });

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
        $(this).next('.custom-file-label').text(file.name);
    });

    // ==================== MODAL AJOUT ====================
    $('#addModal').on('hidden.bs.modal', function() {
        $('#addForm')[0].reset();
        $('#addForm .form-control').removeClass('is-invalid');
        $('#addForm .invalid-feedback').empty();
        $('#photoPreview').attr('src', 'https://ui-avatars.com/api/?name=Photo&background=0D6EFD&color=fff&size=120');
        $('.custom-file-label').text('Choisir photo');
    });

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
                    table.ajax.reload();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').empty();
                    
                    $.each(errors, function(key, value) {
                        $('#' + key).addClass('is-invalid');
                        $('#' + key + '_error').text(value[0]);
                    });
                    toastr.warning('Veuillez corriger les erreurs');
                } else {
                    toastr.error('Une erreur est survenue');
                }
                btn.prop('disabled', false).html(originalText);
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
                    table.ajax.reload();
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
                    let photoUrl = a.photo ? '/storage/' + a.photo : 'https://ui-avatars.com/api/?name=' + a.nom + '+' + a.prenom + '&background=0D6EFD&color=fff&size=150';
                    
                    let html = '<div class="row">';
                    html += '<div class="col-md-4 text-center">';
                    html += '<img src="' + photoUrl + '" class="img-circle elevation-2 mb-3" style="width:150px;height:150px;object-fit:cover;">';
                    html += '<h4>' + a.nom + ' ' + a.prenom + '</h4>';
                    html += '<span class="badge badge-' + (response.data.est_actif ? 'success' : 'secondary') + '">' + (response.data.est_actif ? 'Actif' : 'Inactif') + '</span>';
                    html += '</div>';
                    html += '<div class="col-md-8">';
                    html += '<table class="table table-bordered">';
                    html += '<tr><th>CIN:</th><td>' + (a.cin || 'N/A') + '</td></tr>';
                    html += '<tr><th>Carte N°:</th><td>' + (a.card_id || 'N/A') + '</td></tr>';
                    html += '<tr><th>Téléphone:</th><td>' + a.telephone + '</td></tr>';
                    html += '<tr><th>Email:</th><td>' + (a.email || 'N/A') + '</td></tr>';
                    html += '<tr><th>Sexe:</th><td>' + (a.sexe || 'N/A') + '</td></tr>';
                    html += '<tr><th>Date Naissance:</th><td>' + (a.date_naissance || 'N/A') + '</td></tr>';
                    html += '<tr><th>Âge:</th><td>' + (response.data.age || 'N/A') + ' ans</td></tr>';
                    html += '<tr><th>Adresse:</th><td>' + (a.adresse || 'N/A') + '</td></tr>';
                    html += '</table>';
                    
                    // Historique des abonnements
                    if (abonnements.length > 0) {
                        html += '<h5 class="mt-3">Historique des abonnements</h5>';
                        html += '<table class="table table-sm">';
                        html += '<tr><th>Type</th><th>Début</th><th>Fin</th><th>Montant</th><th>Statut</th></tr>';
                        abonnements.forEach(function(ab) {
                            let statutClass = ab.statut === 'actif' ? 'success' : 'secondary';
                            html += '<tr>';
                            html += '<td>' + ab.type_abonnement + '</td>';
                            html += '<td>' + ab.date_debut + '</td>';
                            html += '<td>' + ab.date_fin + '</td>';
                            html += '<td>' + ab.montant + ' DH</td>';
                            html += '<td><span class="badge badge-' + statutClass + '">' + ab.statut + '</span></td>';
                            html += '</tr>';
                        });
                        html += '</table>';
                    }
                    
                    html += '</div></div>';
                    
                    $('#viewModalContent').html(html);
                }
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
                    let photoUrl = a.photo ? '/storage/' + a.photo : 'https://ui-avatars.com/api/?name=' + a.nom + '+' + a.prenom + '&background=0D6EFD&color=fff&size=120';
                    
                    let html = `
                        <form id="editForm" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" name="id" value="${a.id}">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img id="editPhotoPreview" src="${photoUrl}" 
                                             class="img-circle elevation-2 mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                                        <div class="custom-file">
                                            <input type="file" name="photo" class="custom-file-input" id="edit_photo" accept="image/*">
                                            <label class="custom-file-label" for="edit_photo">Changer photo</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nom</label>
                                                    <input type="text" name="nom" class="form-control" value="${a.nom}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Prénom</label>
                                                    <input type="text" name="prenom" class="form-control" value="${a.prenom}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>CIN</label>
                                                    <input type="text" name="cin" class="form-control" value="${a.cin || ''}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Carte N°</label>
                                                    <input type="text" name="card_id" class="form-control" value="${a.card_id || ''}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Téléphone</label>
                                                    <input type="text" name="telephone" class="form-control" value="${a.telephone}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control" value="${a.email || ''}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Sexe</label>
                                                    <select name="sexe" class="form-control">
                                                        <option value="">Non</option>
                                                        <option value="Homme" ${a.sexe == 'Homme' ? 'selected' : ''}>Homme</option>
                                                        <option value="Femme" ${a.sexe == 'Femme' ? 'selected' : ''}>Femme</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Date Naiss.</label>
                                                    <input type="date" name="date_naissance" class="form-control" value="${a.date_naissance || ''}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Lieu</label>
                                                    <input type="text" name="lieu_naissance" class="form-control" value="${a.lieu_naissance || ''}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Nationalité</label>
                                                    <input type="text" name="nationalite" class="form-control" value="${a.nationalite || 'Marocaine'}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Situation</label>
                                                    <select name="situation_familiale" class="form-control">
                                                        <option value="">Choisir</option>
                                                        <option value="Célibataire" ${a.situation_familiale == 'Célibataire' ? 'selected' : ''}>Célibataire</option>
                                                        <option value="Marié(e)" ${a.situation_familiale == 'Marié(e)' ? 'selected' : ''}>Marié(e)</option>
                                                        <option value="Divorcé(e)" ${a.situation_familiale == 'Divorcé(e)' ? 'selected' : ''}>Divorcé(e)</option>
                                                        <option value="Veuf(ve)" ${a.situation_familiale == 'Veuf(ve)' ? 'selected' : ''}>Veuf(ve)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Profession</label>
                                                    <input type="text" name="profession" class="form-control" value="${a.profession || ''}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Adresse</label>
                                            <textarea name="adresse" class="form-control" rows="2">${a.adresse || ''}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" class="form-control" rows="2">${a.notes || ''}</textarea>
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
                    
                    // Prévisualisation photo
                    $('#edit_photo').on('change', function() {
                        let file = this.files[0];
                        if (file) {
                            let reader = new FileReader();
                            reader.onload = function(e) {
                                $('#editPhotoPreview').attr('src', e.target.result);
                            }
                            reader.readAsDataURL(file);
                        }
                        $(this).next('.custom-file-label').text(file.name);
                    });
                    
                    // Soumission formulaire
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
                                    table.ajax.reload();
                                    toastr.success(response.message);
                                }
                            }
                        });
                    });
                }
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
                    table.ajax.reload();
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
@section('plugins.Select2', true)
@section('plugins.BsCustomFileInput', true)