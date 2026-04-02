@extends('adminlte::page')

@section('title', 'Paramètres')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Paramètres système</h1>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Configuration</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf
                            <div class="form-group">
                                <label for="settingKey">Clé</label>
                                <input type="text" class="form-control" id="settingKey" placeholder="ex: app_name">
                            </div>
                            <div class="form-group">
                                <label for="settingValue">Valeur</label>
                                <input type="text" class="form-control" id="settingValue" placeholder="Valeur">
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Menu sidebar dynamique</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Le menu est lu depuis la base de donnees avec la cle <code>adminlte_menu</code>.
                            Si la valeur est vide, le systeme genere automatiquement le menu a partir des routes disponibles dans <code>web.php</code>.
                        </p>

                        <div class="form-group">
                            <label for="menuJson">JSON du menu</label>
                            <textarea class="form-control" id="menuJson" rows="18">{{ $savedMenuJson }}</textarea>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-info" id="saveMenuBtn">Enregistrer le menu</button>
                            <button type="button" class="btn btn-secondary" id="loadDefaultMenuBtn">Charger le menu par defaut</button>
                            <button type="button" class="btn btn-outline-danger" id="clearMenuBtn">Vider la valeur DB</button>
                        </div>

                        <div class="alert alert-light border">
                            <strong>Menu par defaut</strong>
                            <pre class="mb-0" style="white-space: pre-wrap;">{!! e($defaultMenuJson) !!}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(function() {
    $('#settingsForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("settings.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                key: $('#settingKey').val(),
                value: $('#settingValue').val()
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success');
                    $('#settingKey').val('');
                    $('#settingValue').val('');
                }
            },
            error: function() {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            }
        });
    });

    $('#saveMenuBtn').click(function() {
        const menuValue = $('#menuJson').val();

        if (menuValue.trim() !== '') {
            try {
                JSON.parse(menuValue);
            } catch (e) {
                Swal.fire('Erreur', 'Le JSON du menu est invalide', 'error');
                return;
            }
        }

        $.ajax({
            url: '{{ route("settings.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                key: 'adminlte_menu',
                value: menuValue
            },
            success: function(response) {
                Swal.fire('Succes', response.message, 'success');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue';
                Swal.fire('Erreur', message, 'error');
            }
        });
    });

    $('#loadDefaultMenuBtn').click(function() {
        $('#menuJson').val(@json($defaultMenuJson));
    });

    $('#clearMenuBtn').click(function() {
        $('#menuJson').val('');
    });
});
</script>
@stop
