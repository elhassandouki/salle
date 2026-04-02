@extends('adminlte::page')

@section('title', 'Reclamations d\'Assurance')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-file-medical"></i> Reclamations d'Assurance</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Reclamations</li>
            </ol>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalReclamations }}</h3>
                    <p>Total Reclamations</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $enAttente }}</h3>
                    <p>En attente</p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $approuvees + $remboursees }}</h3>
                    <p>Traitees</p>
                </div>
                <div class="icon"><i class="fas fa-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($totalRemboursable, 2) }} DH</h3>
                    <p>Total remboursable</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title">Liste des reclamations</h3>
                <div class="btn-group mt-2 mt-md-0">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">
                        <i class="fas fa-redo"></i> Reinitialiser
                    </button>
                    <button class="btn btn-sm btn-info" id="refreshTable">
                        <i class="fas fa-sync"></i> Actualiser
                    </button>
                    <button class="btn btn-sm btn-success" id="exportBtn">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="fas fa-plus"></i> Nouvelle reclamation
                    </button>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-2">
                    <label for="filter_search">Recherche</label>
                    <input type="text" class="form-control form-control-sm" id="filter_search" placeholder="Nom, CIN, notes">
                </div>
                <div class="col-md-2">
                    <label for="filter_statut">Statut</label>
                    <select class="form-control form-control-sm" id="filter_statut">
                        <option value="">Tous</option>
                        <option value="en_attente">En attente</option>
                        <option value="approuve">Approuve</option>
                        <option value="refuse">Refuse</option>
                        <option value="rembourse">Rembourse</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_type">Type</label>
                    <select class="form-control form-control-sm" id="filter_type">
                        <option value="">Tous</option>
                        <option value="consultation">Consultation</option>
                        <option value="examen">Examen medical</option>
                        <option value="medicament">Medicament</option>
                        <option value="rehabilitation">Rehabilitation</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_date_debut">Date debut</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_debut">
                </div>
                <div class="col-md-2">
                    <label for="filter_date_fin">Date fin</label>
                    <input type="date" class="form-control form-control-sm" id="filter_date_fin">
                </div>
                <div class="col-md-2">
                    <label for="filter_assurance">Assurance</label>
                    <select class="form-control form-control-sm" id="filter_assurance">
                        <option value="">Toutes</option>
                        @foreach($assurances as $assurance)
                            <option value="{{ $assurance->id }}" {{ (string) $selectedAssuranceId === (string) $assurance->id ? 'selected' : '' }}>
                                {{ $assurance->abonne->nom }} {{ $assurance->abonne->prenom }} - {{ $assurance->company->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="reclamationsTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>Abonne</th>
                        <th>Compagnie</th>
                        <th>Type</th>
                        <th>Montants</th>
                        <th>Dates</th>
                        <th>Statut</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="card-footer">
            <small class="text-muted" id="tableInfo">Chargement des donnees...</small>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nouvelle reclamation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Assurance *</label>
                                <select name="abonne_assurance_id" id="abonne_assurance_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Selectionner une assurance</option>
                                    @foreach($assurances as $assurance)
                                        <option
                                            value="{{ $assurance->id }}"
                                            data-taux="{{ $assurance->company->taux_couverture }}"
                                            data-plafond="{{ $assurance->plafond_annuel }}"
                                            data-utilise="{{ $assurance->montant_utilise }}"
                                        >
                                            {{ $assurance->abonne->nom }} {{ $assurance->abonne->prenom }} - {{ $assurance->company->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted" id="assurance_info"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="consultation">Consultation</option>
                                    <option value="examen">Examen medical</option>
                                    <option value="medicament">Medicament</option>
                                    <option value="rehabilitation">Rehabilitation</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant total (DH) *</label>
                                <input type="number" name="montant_total" id="montant_total" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Montant remboursable (DH)</label>
                                <input type="number" id="montant_remboursable" class="form-control" readonly>
                                <small class="text-muted" id="remboursement_info"></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date de reclamation *</label>
                                <input type="date" name="date_reclamation" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Justificatif</label>
                                <div class="custom-file">
                                    <input type="file" name="justificatif" class="custom-file-input" id="justificatif" accept=".pdf,.jpg,.jpeg,.png">
                                    <label class="custom-file-label" for="justificatif">Choisir un fichier</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Details de la reclamation"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitAddBtn">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="traiterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-check"></i> Traiter la reclamation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="traiterForm">
                @csrf
                <input type="hidden" id="traiter_reclamation_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nouveau statut *</label>
                        <select name="statut" class="form-control" required>
                            <option value="approuve">Approuver</option>
                            <option value="refuse">Refuser</option>
                            <option value="rembourse">Marquer comme rembourse</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de traitement *</label>
                        <input type="date" name="date_traitement" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Notes de traitement</label>
                        <textarea name="notes_traitement" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" id="submitTraiterBtn">
                        <i class="fas fa-check"></i> Traiter
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Supprimer la reclamation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete_id">
                <p class="mb-0">Cette action supprimera definitivement la reclamation selectionnee.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(function () {
    let table;

    function showToast(message, type = 'info') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
            return;
        }

        alert(message);
    }

    function clearValidation(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
    }

    function updateRemboursementPreview() {
        const selected = $('#abonne_assurance_id option:selected');
        const montantTotal = parseFloat($('#montant_total').val() || 0);
        const taux = parseFloat(selected.data('taux') || 0);
        const plafond = parseFloat(selected.data('plafond') || 0);
        const utilise = parseFloat(selected.data('utilise') || 0);
        const solde = Math.max(0, plafond - utilise);
        const remboursement = Math.min((montantTotal * taux) / 100, solde);

        $('#assurance_info').text(selected.val() ? 'Plafond: ' + plafond.toFixed(2) + ' DH | Utilise: ' + utilise.toFixed(2) + ' DH | Solde: ' + solde.toFixed(2) + ' DH' : '');
        $('#montant_remboursable').val(selected.val() ? remboursement.toFixed(2) : '');
        $('#remboursement_info').text(selected.val() ? 'Calcul base sur un taux de couverture de ' + taux + '%' : '');
    }

    table = $('#reclamationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('reclamation_assurances.getData') }}",
            data: function (d) {
                d.filters = {
                    statut: $('#filter_statut').val(),
                    type: $('#filter_type').val(),
                    date_debut: $('#filter_date_debut').val(),
                    date_fin: $('#filter_date_fin').val(),
                    assurance_id: $('#filter_assurance').val()
                };
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'abonne', orderable: false },
            { data: 'company', orderable: false },
            { data: 'type', orderable: false, searchable: false },
            { data: 'montants', orderable: false, searchable: false },
            { data: 'dates', orderable: false, searchable: false },
            { data: 'statut_badge', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        pageLength: 10,
        responsive: true,
        language: {
            processing: "Traitement en cours...",
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ elements",
            info: "Affichage de _START_ a _END_ sur _TOTAL_ elements",
            infoEmpty: "Affichage de 0 a 0 sur 0 elements",
            infoFiltered: "(filtre de _MAX_ elements au total)",
            zeroRecords: "Aucun element a afficher",
            emptyTable: "Aucune donnee disponible",
            paginate: {
                first: "Premier",
                previous: "Precedent",
                next: "Suivant",
                last: "Dernier"
            }
        },
        drawCallback: function () {
            const pageInfo = this.api().page.info();
            if (pageInfo.recordsDisplay === 0) {
                $('#tableInfo').text('Aucune reclamation trouvee');
                return;
            }

            $('#tableInfo').text(
                'Affichage de ' + (pageInfo.start + 1) + ' a ' + pageInfo.end + ' sur ' + pageInfo.recordsDisplay + ' reclamations'
            );
        }
    });

    $('.select2').select2({ dropdownParent: $('#addModal') });
    updateRemboursementPreview();

    $('#filter_search').on('keyup', function () {
        table.search(this.value).draw();
    });

    $('#filter_statut, #filter_type, #filter_date_debut, #filter_date_fin, #filter_assurance').on('change', function () {
        table.draw();
    });

    $('#refreshTable').on('click', function () {
        table.ajax.reload(null, false);
        showToast('Table actualisee', 'success');
    });

    $('#resetFilters').on('click', function () {
        $('#filter_search').val('');
        $('#filter_statut').val('');
        $('#filter_type').val('');
        $('#filter_date_debut').val('');
        $('#filter_date_fin').val('');
        $('#filter_assurance').val('');
        table.search('').draw();
    });

    $('#abonne_assurance_id, #montant_total').on('change keyup', updateRemboursementPreview);

    $('#justificatif').on('change', function () {
        const fileName = this.files.length ? this.files[0].name : 'Choisir un fichier';
        $(this).next('.custom-file-label').text(fileName);
    });

    $('#addForm').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = $('#submitAddBtn');
        const originalHtml = submitBtn.html();
        clearValidation(form);

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: "{{ route('reclamation_assurances.store') }}",
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function (response) {
                $('#addModal').modal('hide');
                form[0].reset();
                $('.custom-file-label').text('Choisir un fichier');
                $('#abonne_assurance_id').val(null).trigger('change');
                updateRemboursementPreview();
                table.ajax.reload(null, false);
                showToast(response.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        const input = form.find('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                    showToast('Veuillez corriger les erreurs du formulaire', 'warning');
                } else {
                    showToast('Erreur lors de la creation', 'error');
                }
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $(document).on('click', '.traiter-btn', function () {
        $('#traiter_reclamation_id').val($(this).data('id'));
        $('#traiterModal').modal('show');
    });

    $('#traiterForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#traiter_reclamation_id').val();
        const submitBtn = $('#submitTraiterBtn');
        const originalHtml = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');

        $.ajax({
            url: "{{ url('reclamation-assurances') }}/" + id + "/traiter",
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                $('#traiterModal').modal('hide');
                $('#traiterForm')[0].reset();
                table.ajax.reload(null, false);
                showToast(response.message, 'success');
            },
            error: function () {
                showToast('Erreur lors du traitement', 'error');
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $(document).on('click', '.delete-btn', function () {
        $('#delete_id').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function () {
        const id = $('#delete_id').val();
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');

        $.ajax({
            url: "{{ url('reclamation-assurances') }}/" + id,
            method: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                $('#deleteModal').modal('hide');
                table.ajax.reload(null, false);
                showToast(response.message, 'success');
            },
            error: function () {
                showToast('Erreur lors de la suppression', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $('#exportBtn').on('click', function () {
        const query = $.param({
            statut: $('#filter_statut').val(),
            type: $('#filter_type').val(),
            date_debut: $('#filter_date_debut').val(),
            date_fin: $('#filter_date_fin').val(),
            assurance_id: $('#filter_assurance').val()
        });

        window.location.href = "{{ route('reclamation_assurances.export') }}?" + query;
    });
});
</script>
@stop

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.Toastr', true)
