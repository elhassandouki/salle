@extends('adminlte::page')

@section('title', 'Gestion des Activites')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-dumbbell"></i> Gestion des Activites</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Activites</li>
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
            <h5><i class="icon fas fa-check"></i> Succes!</h5>
            {{ session('success') }}
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalActivites }}</h3>
                    <p>Total activites</p>
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
                    <p>Activites actives</p>
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
                    <p>Abonnes actifs</p>
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
                    <p>Activites inactives</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des activites</h3>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Reinitialiser
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nouvelle activite
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_search">Recherche</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_search" placeholder="Nom, description...">
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
                        <label for="filter_capacite">Capacite</label>
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
                        <th width="120">Capacite</th>
                        <th width="80">Statut</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Nouvelle activite</h5>
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
                                    <option value="">Selectionner un coach</option>
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
                                <label>Prix mensuel (DH) *</label>
                                <input type="number" name="prix_mensuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix trimestriel (DH) *</label>
                                <input type="number" name="prix_trimestriel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix annuel (DH) *</label>
                                <input type="number" name="prix_annuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Capacite max *</label>
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

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Modifier l'activite</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="nom" class="form-control" required maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Coach</label>
                                <select name="coach_id" class="form-control">
                                    <option value="">Selectionner un coach</option>
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
                                <label>Prix mensuel (DH) *</label>
                                <input type="number" name="prix_mensuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix trimestriel (DH) *</label>
                                <input type="number" name="prix_trimestriel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Prix annuel (DH) *</label>
                                <input type="number" name="prix_annuel" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Capacite max *</label>
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
                    <button type="submit" class="btn btn-primary">Mettre a jour</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Details de l'activite</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Supprimer l'activite</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Etes-vous sur de vouloir supprimer cette activite ?</p>
                <p class="text-danger"><strong>Cette action est irreversible !</strong></p>
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
            lengthMenu: "Afficher _MENU_ elements",
            info: "Affichage de _START_ a _END_ sur _TOTAL_ elements",
            infoEmpty: "Affichage de 0 a 0 sur 0 elements",
            infoFiltered: "(filtre de _MAX_ elements)",
            emptyTable: "Aucune donnee disponible",
            paginate: {
                first: "Premier",
                previous: "Precedent",
                next: "Suivant",
                last: "Dernier"
            }
        }
    });

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

    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        $('#viewModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>');
        $('#viewModal').modal('show');

        $.ajax({
            url: "{{ url('activites') }}/" + id,
            type: "GET",
            success: function(response) {
                if (response.success) {
                    let activite = response.data;
                    let coach = activite.coach ? activite.coach.nom + ' ' + activite.coach.prenom : 'Non assigne';
                    let subscriptions = activite.subscriptions || [];
                    let rows = subscriptions.length
                        ? subscriptions.map(function(subscription) {
                            let abonne = subscription.abonne ? subscription.abonne.nom + ' ' + subscription.abonne.prenom : 'N/A';
                            return `<tr>
                                <td>${abonne}</td>
                                <td>${subscription.type_abonnement || '-'}</td>
                                <td>${subscription.date_debut || '-'}</td>
                                <td>${subscription.date_fin || '-'}</td>
                                <td>${subscription.statut || '-'}</td>
                            </tr>`;
                        }).join('')
                        : '<tr><td colspan="5" class="text-center text-muted">Aucun abonnement</td></tr>';

                    $('#viewModalBody').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr><th>Nom</th><td>${activite.nom}</td></tr>
                                    <tr><th>Description</th><td>${activite.description || 'N/A'}</td></tr>
                                    <tr><th>Coach</th><td>${coach}</td></tr>
                                    <tr><th>Statut</th><td>${activite.statut}</td></tr>
                                    <tr><th>Capacite max</th><td>${activite.capacite_max ?? 'Illimite'}</td></tr>
                                    <tr><th>Couleur</th><td><span class="badge" style="background:${activite.couleur || '#007bff'};">${activite.couleur || '#007bff'}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr><th>Prix mensuel</th><td>${activite.prix_mensuel} DH</td></tr>
                                    <tr><th>Prix trimestriel</th><td>${activite.prix_trimestriel} DH</td></tr>
                                    <tr><th>Prix annuel</th><td>${activite.prix_annuel} DH</td></tr>
                                    <tr><th>Cree le</th><td>${activite.created_at || '-'}</td></tr>
                                    <tr><th>Mis a jour le</th><td>${activite.updated_at || '-'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <h6 class="mt-3">Abonnements lies</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Abonne</th>
                                        <th>Type</th>
                                        <th>Debut</th>
                                        <th>Fin</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html('<div class="alert alert-danger">Impossible de charger les details.</div>');
                }
            },
            error: function() {
                $('#viewModalBody').html('<div class="alert alert-danger">Erreur lors du chargement.</div>');
            }
        });
    });

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
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showToast(Object.values(xhr.responseJSON.errors)[0][0], 'error');
                } else {
                    showToast('Erreur lors de la mise a jour', 'error');
                }
            }
        });
    });

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
