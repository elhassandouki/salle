@extends('adminlte::page')

@section('title', 'Gestion des Activités')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-dumbbell"></i> Gestion des Activités</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Activités</li>
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
                    <h3>{{ $totalActivites }}</h3>
                    <p>Total Activités</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalActives }}</h3>
                    <p>Activités Actives</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalAbonnesActifs }}</h3>
                    <p>Abonnés Actifs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalInactives }}</h3>
                    <p>Activités Inactives</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte principale -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des Activités</h3>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nouvelle Activité
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
                                   placeholder="Nom, description...">
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
                        <label for="filter_statut">Statut</label>
                        <select class="form-control form-control-sm" id="filter_statut">
                            <option value="">Tous</option>
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_coach">Coach</label>
                        <select class="form-control form-control-sm" id="filter_coach">
                            <option value="">Tous</option>
                            @foreach($coaches as $coach)
                                <option value="{{ $coach->id }}">{{ $coach->nom }} {{ $coach->prenom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_capacite">Capacité</label>
                        <select class="form-control form-control-sm" id="filter_capacite">
                            <option value="">Toutes</option>
                            <option value="pleine">Pleine</option>
                            <option value="disponible">Avec places</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="activitesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th>Nom</th>
                        <th width="150">Description</th>
                        <th width="120">Coach</th>
                        <th width="150">Prix (M/T/A)</th>
                        <th width="120">Capacité</th>
                        <th width="80">Statut</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout/Édition -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Nouvelle Activité</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="nom" class="form-control" required maxlength="100">
                                <div class="invalid-feedback" id="nom_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Coach</label>
                                <select name="coach_id" class="form-control">
                                    <option value="">Sélectionner un coach</option>
                                    @foreach($coaches as $coach)
                                        <option value="{{ $coach->id }}">{{ $coach->nom }} {{ $coach->prenom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix Mensuel (DH) *</label>
                                <input type="number" name="prix_mensuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix Trimestriel (DH) *</label>
                                <input type="number" name="prix_trimestriel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix Annuel (DH) *</label>
                                <input type="number" name="prix_annuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Capacité Max *</label>
                                <input type="number" name="capacite_max" class="form-control" required min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Couleur</label>
                                <input type="color" name="couleur" class="form-control" value="#007bff">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Statut *</label>
                        <select name="statut" class="form-control" required>
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Modifier l'Activité</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Même contenu que addModal -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Supprimer l'Activité</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette activité ?</p>
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
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#activitesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('activites.getData') }}",
            data: function(d) {
                d.filters = {
                    search: $('#filter_search').val(),
                    statut: $('#filter_statut').val(),
                    coach_id: $('#filter_coach').val(),
                    capacite: $('#filter_capacite').val()
                };
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nom', name: 'nom' },
            { data: 'description', name: 'description', orderable: false },
            { data: 'coach', name: 'coach', orderable: false },
            { data: 'prix', name: 'prix_mensuel', orderable: false },
            { data: 'capacite', name: 'capacite_max', orderable: false },
            { data: 'statut_badge', name: 'statut', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        language: {
            processing: "Traitement en cours...",
            search: "Rechercher&nbsp;:",
            lengthMenu: "Afficher _MENU_ éléments",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Affichage de 0 à 0 sur 0 éléments",
            infoFiltered: "(filtré de _MAX_ éléments)",
            emptyTable: "Aucune donnée disponible",
            paginate: {
                first: "Premier",
                previous: "Précédent",
                next: "Suivant",
                last: "Dernier"
            }
        }
    });

    // Filtres
    $('#filter_search').on('keyup', function() {
        table.search(this.value).draw();
    });

    $('#filter_statut, #filter_coach, #filter_capacite').on('change', function() {
        table.draw();
    });

    $('#resetFilters').on('click', function() {
        $('#filter_search').val('');
        $('#filter_statut').val('');
        $('#filter_coach').val('');
        $('#filter_capacite').val('');
        table.search('').draw();
    });

    $('#refreshTable').on('click', function() {
        table.ajax.reload(null, false);
    });

    // Ajout
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "{{ route('activites.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                    $('#addForm')[0].reset();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('[name="' + key + '"]').addClass('is-invalid')
                            .next('.invalid-feedback').text(value[0]);
                    });
                }
            }
        });
    });

    // Édition
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: "{{ url('activites') }}/" + id + "/edit",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    let activite = response.data;
                    $('#edit_id').val(activite.id);
                    $('#editForm [name="nom"]').val(activite.nom);
                    $('#editForm [name="description"]').val(activite.description);
                    $('#editForm [name="coach_id"]').val(activite.coach_id);
                    $('#editForm [name="prix_mensuel"]').val(activite.prix_mensuel);
                    $('#editForm [name="prix_trimestriel"]').val(activite.prix_trimestriel);
                    $('#editForm [name="prix_annuel"]').val(activite.prix_annuel);
                    $('#editForm [name="capacite_max"]').val(activite.capacite_max);
                    $('#editForm [name="couleur"]').val(activite.couleur);
                    $('#editForm [name="statut"]').val(activite.statut);
                    $('#editModal').modal('show');
                }
            }
        });
    });

    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#edit_id').val();
        
        $.ajax({
            url: "{{ url('activites') }}/" + id,
            type: "PUT",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToast(response.message, 'success');
                }
            }
        });
    });

    // Suppression
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        $('#delete_id').val(id);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        let id = $('#delete_id').val();
        
        $.ajax({
            url: "{{ url('activites') }}/" + id,
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
            }
        });
    });

    function showToast(message, type = 'info') {
        let toast = $(`
            <div class="toast bg-${type} text-white" style="position: fixed; bottom: 20px; right: 20px; z-index: 1050;">
                <div class="toast-body">
                    <button type="button" class="close text-white ml-2 mb-1">
                        <span>&times;</span>
                    </button>
                    ${message}
                </div>
            </div>
        `);
        
        $('body').append(toast);
        setTimeout(() => toast.remove(), 5000);
    }
});
</script>
@stop