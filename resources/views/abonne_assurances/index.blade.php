@extends('adminlte::page')

@section('title', 'Assurances des Abonnأ©s')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-user-shield"></i> Assurances des Abonnأ©s</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Assurances Abonnأ©s</li>
            </ol>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ $totalAssurances }}</h3><p>Total Assurances</p></div><div class="icon"><i class="fas fa-shield-alt"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ $totalActives }}</h3><p>Actives</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3>{{ $totalExpirees }}</h3><p>Expirees</p></div><div class="icon"><i class="fas fa-ban"></i></div></div></div>
        <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ $totalResiliees }}</h3><p>Resiliees</p></div><div class="icon"><i class="fas fa-times-circle"></i></div></div></div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Liste des assurances</h3>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" id="resetFilters"><i class="fas fa-redo"></i> Rأ©initialiser</button>
                    <button class="btn btn-sm btn-info" id="refreshTable"><i class="fas fa-sync"></i> Actualiser</button>
                    <button class="btn btn-sm btn-success" id="exportBtn"><i class="fas fa-download"></i> Export</button>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal"><i class="fas fa-plus"></i> Nouvelle assurance</button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="filter_search" placeholder="Nom, CIN, notes..."></div>
                <div class="col-md-3"><select class="form-control form-control-sm" id="filter_statut"><option value="">Tous les statuts</option><option value="actif">Actif</option><option value="expirط£آ©">Expirأ©</option><option value="resilie">Rأ©siliأ©</option></select></div>
                <div class="col-md-3"><select class="form-control form-control-sm" id="filter_abonne"><option value="">Tous les abonnأ©s</option>@foreach($abonnes as $abonne)<option value="{{ $abonne->id }}">{{ $abonne->nom }} {{ $abonne->prenom }}</option>@endforeach</select></div>
                <div class="col-md-2"><select class="form-control form-control-sm" id="filter_type"><option value="">Toutes durأ©es</option><option value="mensuel">Mensuel</option><option value="trimestriel">Trimestriel</option><option value="semestriel">Semestriel</option><option value="annuel">Annuel</option></select></div>
            </div>
        </div>
        <div class="card-body">
            <table id="assurancesTable" class="table table-bordered table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Abonnأ©</th>
                        <th>Durأ©e</th>
                        <th>Contrat</th>
                        <th>Montant</th>
                        <th>Jours</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="card-footer"><small id="tableInfo" class="text-muted">Chargement...</small></div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary"><h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nouvelle assurance</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="addForm">@csrf
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Abonnأ© *</label><select name="abonne_id" class="form-control select2" required style="width:100%"><option value="">Sأ©lectionner</option>@foreach($abonnes as $abonne)<option value="{{ $abonne->id }}">{{ $abonne->nom }} {{ $abonne->prenom }} ({{ $abonne->cin ?? 'N/C' }})</option>@endforeach</select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Durأ©e *</label><select name="type_abonnement" class="form-control" required><option value="mensuel">Mensuel</option><option value="trimestriel" selected>Trimestriel</option><option value="semestriel">Semestriel</option><option value="annuel">Annuel</option></select></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Date dأ©but *</label><input type="date" name="date_debut" class="form-control" required value="{{ date('Y-m-d') }}"></div></div>
                <div class="col-md-6"><div class="form-group"><label>Montant assurance (DH) *</label><input type="number" name="montant_assurance" class="form-control" required min="0" step="0.01" placeholder="0.00"></div></div>
            </div>
            <div class="form-group"><label>Statut *</label><select name="statut" class="form-control" required><option value="actif">Actif</option><option value="expirط£آ©">Expirأ©</option><option value="resilie">Rأ©siliأ©</option></select></div>
            <div class="form-group mb-0"><label>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Notes supplأ©mentaires..."></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary" id="submitAddBtn"><i class="fas fa-save"></i> Enregistrer</button><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Annuler</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="renouvelerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-info"><h5 class="modal-title"><i class="fas fa-redo"></i> Renouveler assurance</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="renouvelerForm">@csrf<input type="hidden" name="assurance_id" id="renouveler_assurance_id">
        <div class="modal-body">
            <div class="form-group"><label>Nouvelle durأ©e *</label><select name="type_abonnement" class="form-control" required><option value="mensuel">Mensuel</option><option value="trimestriel" selected>Trimestriel</option><option value="semestriel">Semestriel</option><option value="annuel">Annuel</option></select></div>
            <div class="form-group"><label>Nouveau montant (DH) *</label><input type="number" name="montant_assurance" class="form-control" required min="0" step="0.01" value="0"></div>
            <div class="form-group mb-0"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-info" id="submitRenouvelerBtn"><i class="fas fa-redo"></i> Renouveler</button><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Annuler</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger"><h5 class="modal-title"><i class="fas fa-trash"></i> Supprimer assurance</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body"><p>أٹtes-vous sأ»r de vouloir supprimer cette assurance ?</p><input type="hidden" id="delete_id"></div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" id="confirmDelete"><i class="fas fa-trash"></i> Supprimer</button><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Annuler</button></div>
</div></div></div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>.small-box>.inner{padding:10px}.small-box h3{font-size:38px;font-weight:bold;margin:0 0 10px 0}.small-box .icon{position:absolute;top:-10px;right:10px;font-size:90px;color:rgba(0,0,0,.15)}.select2-container{width:100%!important}</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    let table = null;
    $('.select2').select2({dropdownParent: $('#addModal')});
    function showToast(message, type = 'info') { let color = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info'; let toast = $(`<div class="toast ${color} text-white" style="position:fixed;bottom:20px;right:20px;z-index:1050;min-width:300px;"><div class="toast-body">${message}</div></div>`); $('body').append(toast); setTimeout(() => toast.fadeOut(300, () => toast.remove()), 4000); }
    function clearErrors(form){form.find('.is-invalid').removeClass('is-invalid');form.find('.invalid-feedback').remove();}

    table = $('#assurancesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('abonne_assurances.getData') }}",
            data: function (d) {
                d.filters = {
                    statut: $('#filter_statut').val(),
                    abonne_id: $('#filter_abonne').val(),
                    type_abonnement: $('#filter_type').val()
                };
            }
        },
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'abonne', orderable: false},
            {data: 'duree', orderable: false},
            {data: 'contrat', orderable: false, searchable: false},
            {data: 'montant', orderable: false, searchable: false},
            {data: 'jours_restants', orderable: false, searchable: false},
            {data: 'statut_badge', orderable: false, searchable: false},
            {data: 'action', orderable: false, searchable: false}
        ],
        pageLength: 10,
        order: [[0, 'desc']],
        drawCallback: function(){const info=this.api().page.info();$('#tableInfo').text(info.recordsDisplay?`Affichage de ${info.start+1} أ  ${info.end} sur ${info.recordsDisplay} assurances`:'Aucune donnأ©e');}
    });

    $('#filter_search').on('keyup', function(){table.search(this.value).draw();});
    $('#filter_statut,#filter_abonne,#filter_type').on('change', function(){table.draw();});
    $('#resetFilters').on('click', function(){$('#filter_search').val('');$('#filter_statut,#filter_abonne,#filter_type').val('');table.search('').draw();});
    $('#refreshTable').on('click', function(){table.ajax.reload(null, false);showToast('Table actualisأ©e', 'success');});

    $('#addForm').on('submit', function(e){
        e.preventDefault();
        const form=$(this), btn=$('#submitAddBtn'), original=btn.html();
        clearErrors(form);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        $.post("{{ route('abonne_assurances.store') }}", form.serialize())
            .done(function(response){$('#addModal').modal('hide');table.ajax.reload(null,false);showToast(response.message,'success');form[0].reset();$('.select2').val(null).trigger('change');})
            .fail(function(xhr){if(xhr.status===422&&xhr.responseJSON?.errors){$.each(xhr.responseJSON.errors,function(key,value){const input=form.find(`[name="${key}"]`);input.addClass('is-invalid');input.after('<div class="invalid-feedback">'+value[0]+'</div>');});showToast('Veuillez corriger les erreurs','warning');}else{showToast(xhr.responseJSON?.message||'Erreur lors de la creation','error');}})
            .always(function(){btn.prop('disabled', false).html(original);});
    });

    $(document).on('click','.reclamation-btn',function(){window.location.href="{{ route('reclamation_assurances.index') }}?assurance_id="+$(this).data('id');});
    $(document).on('click','.renouveler-btn',function(){$('#renouveler_assurance_id').val($(this).data('id'));$('#renouvelerModal').modal('show');});
    $('#renouvelerForm').on('submit', function(e){
        e.preventDefault();
        const id=$('#renouveler_assurance_id').val(), btn=$('#submitRenouvelerBtn'), original=btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Renouvellement...');
        $.post("{{ url('abonne-assurances') }}/"+id+"/renouveler", $(this).serialize())
            .done(function(response){$('#renouvelerModal').modal('hide');table.ajax.reload(null,false);showToast(response.message,'success');$('#renouvelerForm')[0].reset();})
            .fail(function(xhr){showToast(xhr.responseJSON?.message||'Erreur lors du renouvellement','error');})
            .always(function(){btn.prop('disabled', false).html(original);});
    });

    $(document).on('click','.delete-btn',function(){$('#delete_id').val($(this).data('id'));$('#deleteModal').modal('show');});
    $('#confirmDelete').on('click', function(){
        const id=$('#delete_id').val(), btn=$(this), original=btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');
        $.ajax({url:"{{ url('abonne-assurances') }}/"+id,type:'DELETE',data:{_token:"{{ csrf_token() }}"}})
            .done(function(response){$('#deleteModal').modal('hide');table.ajax.reload(null,false);showToast(response.message,'success');})
            .fail(function(xhr){showToast(xhr.responseJSON?.message||'Erreur lors de la suppression','error');})
            .always(function(){btn.prop('disabled', false).html(original);});
    });

    $('#exportBtn').on('click', function(){window.location.href="{{ route('abonne_assurances.export') }}?"+$.param({statut:$('#filter_statut').val(),abonne_id:$('#filter_abonne').val(),type_abonnement:$('#filter_type').val()});});
});
</script>
@stop

