@extends('adminlte::page')
@section('title', 'Gestion des subscriptions')

@section('content_header')
<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1"><i class="fas fa-id-card"></i> Gestion des subscriptions</h1>
            <small class="text-muted">Suivi des abonnements, paiements et renouvellements</small>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus"></i> Nouveau abonnement
        </button>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ $totalAbonnements }}</h3><p>Total</p></div><div class="icon"><i class="fas fa-id-card"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ $totalActifs }}</h3><p>Actives</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ $totalExpires }}</h3><p>Expirees</p></div><div class="icon"><i class="fas fa-ban"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ $totalExpirant }}</h3><p>Bientot</p></div><div class="icon"><i class="fas fa-clock"></i></div></div></div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title">Gestion des subscriptions</h3>
                <div class="btn-group mt-2 mt-md-0">
                    <button class="btn btn-sm btn-secondary" id="resetFilters">Reset</button>
                    <button class="btn btn-sm btn-info" id="refreshTable">Refresh</button>
                    <button class="btn btn-sm btn-success" id="exportBtn">Export</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-2"><input class="form-control form-control-sm" id="filter_search" placeholder="Recherche"></div>
                <div class="col-md-2"><select class="form-control form-control-sm" id="filter_statut"><option value="">Tous</option><option value="actif">Actif</option><option value="expire">Expire</option><option value="suspendu">Suspendu</option></select></div>
                <div class="col-md-2"><select class="form-control form-control-sm" id="filter_activite"><option value="">Activite</option>@foreach($activites as $activite)<option value="{{ $activite->id }}">{{ $activite->nom }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-control form-control-sm" id="filter_type"><option value="">Type</option><option value="mensuel">Mensuel</option><option value="trimestriel">Trimestriel</option><option value="annuel">Annuel</option></select></div>
                <div class="col-md-2"><input type="date" class="form-control form-control-sm" id="filter_date_debut"></div>
                <div class="col-md-2"><input type="date" class="form-control form-control-sm" id="filter_date_fin"></div>
            </div>
        </div>
        <div class="card-body">
            <table id="subscriptionsTable" class="table table-bordered table-striped w-100">
                <thead><tr><th>#</th><th>Abonne</th><th>Activite</th><th>Type</th><th>Dates</th><th>Montants</th><th>Jours</th><th>Statut</th><th>Actions</th></tr></thead>
            </table>
        </div>
        <div class="card-footer"><small id="tableInfo" class="text-muted">Chargement...</small></div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title">Subscription + paiement + assurance</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="addForm">@csrf
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Abonne *</label><select name="abonne_id" class="form-control select2" required style="width:100%"><option value="">Choisir</option>@foreach($abonnes as $abonne)<option value="{{ $abonne->id }}">{{ $abonne->nom }} {{ $abonne->prenom }}</option>@endforeach</select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Activite *</label><select name="service_id" id="service_id" class="form-control select2" required style="width:100%"><option value="">Choisir</option>@foreach($activites as $activite)<option value="{{ $activite->id }}" data-prix-mensuel="{{ $activite->prix_mensuel }}" data-prix-trimestriel="{{ $activite->prix_trimestriel }}" data-prix-annuel="{{ $activite->prix_annuel }}">{{ $activite->nom }}</option>@endforeach</select></div></div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Type *</label><select name="type_abonnement" id="type_abonnement" class="form-control" required><option value="mensuel">Mensuel</option><option value="trimestriel">Trimestriel</option><option value="annuel">Annuel</option></select></div></div>
                <div class="col-md-3"><div class="form-group"><label>Date debut *</label><input type="date" name="date_debut" id="date_debut" value="{{ date('Y-m-d') }}" class="form-control" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Total brut *</label><input type="number" name="montant" id="montant_abonnement" class="form-control" min="0" step="0.01" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Remise</label><input type="number" name="remise" id="remise_abonnement" value="0" class="form-control" min="0" step="0.01"></div></div>
            </div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Total a payer</label><input type="number" id="montant_total_apercu" class="form-control" readonly></div></div>
                <div class="col-md-4"><div class="form-group"><label>Paiement initial</label><input type="number" name="montant_paye_initial" id="montant_paye_initial" value="0" class="form-control" min="0" step="0.01"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Statut *</label><select name="statut" class="form-control" required><option value="actif">Actif</option><option value="suspendu">Suspendu</option></select></div></div>
            </div>
            <hr>
            <h6 class="mb-3">Assurance optionnelle</h6>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Duree assurance</label><select name="assurance_type_abonnement" id="assurance_type_abonnement" class="form-control"><option value="">Sans assurance</option><option value="mensuel">Mensuel</option><option value="trimestriel">Trimestriel</option><option value="semestriel">Semestriel</option><option value="annuel">Annuel</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Montant assurance</label><input type="number" name="assurance_montant" id="assurance_montant" class="form-control" min="0" step="0.01" value="0"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Paiement assurance</label><input type="number" name="assurance_montant_paye_initial" id="assurance_montant_paye_initial" value="0" class="form-control" min="0" step="0.01"></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Total global</label><input type="number" id="montant_global_apercu" class="form-control" readonly></div></div>
                <div class="col-md-6"><div class="alert alert-light border mb-0">L'assurance se cree via montant + duree, sans compagnie.</div></div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Mode paiement</label><select name="mode_paiement" class="form-control"><option value="">Choisir</option><option value="especes">Especes</option><option value="carte">Carte</option><option value="cheque">Cheque</option><option value="virement">Virement</option></select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Date paiement</label><input type="datetime-local" name="date_paiement" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Reference</label><input type="text" name="reference" class="form-control"></div></div>
            </div>
            <div class="form-group"><label>Notes paiement</label><input type="text" name="notes_paiement" class="form-control"></div>
            <div class="form-group mb-0"><label>Notes subscription</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary" id="submitAddBtn">Enregistrer</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="paiementModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success"><h5 class="modal-title">Paiement</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="paiementForm">@csrf<input type="hidden" name="subscription_id" id="paiement_subscription_id">
        <div class="modal-body">
            <div class="form-group"><label>Montant *</label><input type="number" name="montant" class="form-control" min="0" step="0.01" required></div>
            <div class="form-group"><label>Mode *</label><select name="mode_paiement" class="form-control" required><option value="especes">Especes</option><option value="carte">Carte</option><option value="cheque">Cheque</option><option value="virement">Virement</option></select></div>
            <div class="form-group"><label>Date *</label><input type="datetime-local" name="date_paiement" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
            <div class="form-group"><label>Reference</label><input type="text" name="reference" class="form-control"></div>
            <div class="form-group mb-0"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success" id="submitPaiementBtn">Valider</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Details de la subscription</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Chargement...</div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="renouvelerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-warning"><h5 class="modal-title">Renouveler</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><input type="hidden" id="renouveler_id"><p class="mb-0">Creer une nouvelle subscription depuis la fin actuelle.</p></div><div class="modal-footer"><button type="button" class="btn btn-warning" id="confirmRenouveler">Renouveler</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button></div></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header bg-danger"><h5 class="modal-title">Supprimer</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><input type="hidden" id="delete_id"><p class="mb-0">Suppression seulement si aucun paiement n'est lie.</p></div><div class="modal-footer"><button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button></div></div></div></div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
.select2-container { width: 100% !important; }
.subscription-stat-card { border: 1px solid #dee2e6; border-radius: .5rem; padding: 1rem; height: 100%; }
.subscription-stat-card .label { color: #6c757d; font-size: .85rem; margin-bottom: .25rem; }
.subscription-stat-card .value { font-weight: 600; }
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    let table;
    $('.select2').select2({ dropdownParent: $('#addModal') });

    function toast(message, type = 'info') {
        const color = type === 'error' ? 'bg-danger' : type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning' : 'bg-info';
        const t = $(`<div class="toast ${color} text-white" style="position:fixed;bottom:20px;right:20px;z-index:2000"><div class="toast-body">${message}</div></div>`);
        $('body').append(t);
        setTimeout(() => t.fadeOut(300, () => t.remove()), 3000);
    }

    function clearErrors(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
    }

    function recalc() {
        const service = $('#service_id option:selected');
        const type = $('#type_abonnement').val();
        const brut = parseFloat(service.data('prix-' + type) || 0);
        const remise = parseFloat($('#remise_abonnement').val() || 0);
        const total = Math.max(0, brut - remise);
        const assuranceType = $('#assurance_type_abonnement').val();
        const assurancePrix = Math.max(0, parseFloat($('#assurance_montant').val() || 0));
        const assuranceEnabled = assuranceType !== '' && assurancePrix > 0;
        const totalGlobal = total + (assuranceEnabled ? assurancePrix : 0);

        $('#montant_abonnement').val(brut ? brut.toFixed(2) : '');
        $('#montant_total_apercu').val(brut ? total.toFixed(2) : '');
        $('#montant_global_apercu').val(totalGlobal ? totalGlobal.toFixed(2) : '');

        if (parseFloat($('#montant_paye_initial').val() || 0) > total) {
            $('#montant_paye_initial').val(total.toFixed(2));
        }

        if (parseFloat($('#assurance_montant_paye_initial').val() || 0) > (assuranceEnabled ? assurancePrix : 0)) {
            $('#assurance_montant_paye_initial').val(assuranceEnabled ? assurancePrix.toFixed(2) : '0');
        }
    }

    $('#service_id,#type_abonnement,#remise_abonnement,#montant_paye_initial,#assurance_type_abonnement,#assurance_montant,#assurance_montant_paye_initial').on('change keyup', recalc);
    recalc();

    table = $('#subscriptionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('abonnements.getData') }}",
            data: d => {
                d.filters = {
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
            { data: 'abonne', orderable: false },
            { data: 'activite', orderable: false, searchable: false },
            { data: 'type', orderable: false },
            { data: 'dates', orderable: false, searchable: false },
            { data: 'montant', orderable: false, searchable: false },
            { data: 'jours_restants', orderable: false, searchable: false },
            { data: 'statut_badge', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            const info = this.api().page.info();
            $('#tableInfo').text(info.recordsDisplay ? `Affichage de ${info.start + 1} a ${info.end} sur ${info.recordsDisplay}` : 'Aucune donnee');
        }
    });

    $('#filter_search').on('keyup', function () { table.search(this.value).draw(); });
    $('#filter_statut,#filter_activite,#filter_type,#filter_date_debut,#filter_date_fin').on('change', () => table.draw());
    $('#resetFilters').on('click', function () {
        $('#filter_search,#filter_date_debut,#filter_date_fin').val('');
        $('#filter_statut,#filter_activite,#filter_type').val('');
        table.search('').draw();
    });
    $('#refreshTable').on('click', function () { table.ajax.reload(null, false); toast('Table actualisee', 'success'); });

    $('#addForm').on('submit', function (e) {
        e.preventDefault();
        const form = $(this);
        const button = $('#submitAddBtn');
        const original = button.html();
        clearErrors(form);
        button.prop('disabled', true).text('...');

        $.post("{{ route('abonnements.store') }}", form.serialize())
            .done(response => {
                $('#addModal').modal('hide');
                form[0].reset();
                $('.select2').val(null).trigger('change');
                $('#montant_paye_initial').val('0');
                $('#assurance_montant_paye_initial').val('0');
                recalc();
                table.ajax.reload(null, false);
                toast(response.message, 'success');
            })
            .fail(xhr => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, (key, value) => {
                        const input = form.find(`[name="${key}"]`);
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                    });
                    toast('Corriger les erreurs', 'warning');
                } else {
                    toast(xhr.responseJSON?.message || 'Erreur creation', 'error');
                }
            })
            .always(() => button.prop('disabled', false).html(original));
    });

    $(document).on('click', '.paiement-btn', function () {
        $('#paiement_subscription_id').val($(this).data('id'));
        $('#paiementModal').modal('show');
    });

    $('#paiementForm').on('submit', function (e) {
        e.preventDefault();
        const button = $('#submitPaiementBtn');
        const original = button.html();
        button.prop('disabled', true).text('...');

        $.post("{{ route('paiements.store') }}", $(this).serialize())
            .done(response => {
                $('#paiementModal').modal('hide');
                this.reset();
                table.ajax.reload(null, false);
                toast(response.message, 'success');
            })
            .fail(xhr => toast(xhr.responseJSON?.message || 'Erreur paiement', 'error'))
            .always(() => button.prop('disabled', false).html(original));
    });

    $(document).on('click', '.view-btn', function () {
        const id = $(this).data('id');
        $('#viewModalContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Chargement...</div></div>');
        $('#viewModal').modal('show');

        $.get("{{ url('abonnements') }}/" + id, function (response) {
            const a = response.abonnement;
            const statusClass = a.statut.toLowerCase() === 'actif' ? 'success' : (a.statut.toLowerCase() === 'expire' ? 'danger' : 'warning');
            const paiementsRows = (a.paiements || []).length
                ? a.paiements.map(p => `
                    <tr>
                        <td>${p.date_paiement || '-'}</td>
                        <td>${p.montant}</td>
                        <td>${p.mode_paiement || '-'}</td>
                        <td>${p.reference || '-'}</td>
                        <td>${p.notes || '-'}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="text-center text-muted">Aucun paiement</td></tr>';

            $('#viewModalContent').html(`
                <div class="row mb-3">
                    <div class="col-md-4 mb-3">
                        <div class="subscription-stat-card">
                            <div class="label">Abonne</div>
                            <div class="value">${a.abonne}</div>
                            <div class="small text-muted mt-1">CIN: ${a.abonne_cin || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="subscription-stat-card">
                            <div class="label">Activite</div>
                            <div class="value">${a.service}</div>
                            <div class="small text-muted mt-1">Type: ${a.type}</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="subscription-stat-card">
                            <div class="label">Statut</div>
                            <div class="value"><span class="badge badge-${statusClass}">${a.statut}</span></div>
                            <div class="small text-muted mt-1">Jours restants: ${a.jours_restants}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <div class="card mb-0">
                            <div class="card-header"><strong>Informations abonnement</strong></div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-bordered mb-0">
                                    <tr><th width="40%">Date debut</th><td>${a.date_debut || '-'}</td></tr>
                                    <tr><th>Date fin</th><td>${a.date_fin || '-'}</td></tr>
                                    <tr><th>Montant brut</th><td>${a.montant_brut}</td></tr>
                                    <tr><th>Remise</th><td>${a.remise}</td></tr>
                                    <tr><th>Total</th><td>${a.montant}</td></tr>
                                    <tr><th>Paye</th><td>${a.montant_paye}</td></tr>
                                    <tr><th>Reste</th><td>${a.reste}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="card mb-0">
                            <div class="card-header"><strong>Suivi</strong></div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-bordered mb-0">
                                    <tr><th width="40%">Nombre paiements</th><td>${a.paiements_count}</td></tr>
                                    <tr><th>Cree le</th><td>${a.created_at || '-'}</td></tr>
                                    <tr><th>Mis a jour le</th><td>${a.updated_at || '-'}</td></tr>
                                    <tr><th>Notes</th><td>${a.notes || '<span class="text-muted">Aucune note</span>'}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-2 mb-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Historique des paiements</strong>
                        <span class="badge badge-info">${a.paiements_count}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Mode</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>${paiementsRows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `);
        }).fail(xhr => {
            $('#viewModalContent').html('<div class="alert alert-danger mb-0">' + (xhr.responseJSON?.message || 'Erreur chargement') + '</div>');
        });
    });

    $(document).on('click', '.renouveler-btn', function () {
        $('#renouveler_id').val($(this).data('id'));
        $('#renouvelerModal').modal('show');
    });

    $('#confirmRenouveler').on('click', function () {
        const id = $('#renouveler_id').val();
        $.post("{{ url('abonnements') }}/" + id + "/renew", { _token: "{{ csrf_token() }}" })
            .done(response => {
                $('#renouvelerModal').modal('hide');
                table.ajax.reload(null, false);
                toast(response.message, 'success');
            })
            .fail(xhr => toast(xhr.responseJSON?.message || 'Erreur renouvellement', 'error'));
    });

    $(document).on('click', '.delete-btn', function () {
        $('#delete_id').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function () {
        const id = $('#delete_id').val();
        $.ajax({
            url: "{{ url('abonnements') }}/" + id,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" }
        }).done(response => {
            $('#deleteModal').modal('hide');
            table.ajax.reload(null, false);
            toast(response.message, 'success');
        }).fail(xhr => toast(xhr.responseJSON?.message || 'Erreur suppression', 'error'));
    });

    $('#exportBtn').on('click', function () {
        window.location.href = "{{ route('abonnements.export') }}?" + $.param({
            statut: $('#filter_statut').val(),
            activite_id: $('#filter_activite').val(),
            type: $('#filter_type').val()
        });
    });
});
</script>
@stop
