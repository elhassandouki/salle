@extends('adminlte::page')

@section('title', $stateTitle)

@section('content_header')
<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1"><i class="fas fa-table mr-2"></i>{{ $stateTitle }}</h1>
            <small class="text-muted">Page dediee aux etats filtres par date, service et type de donnees.</small>
        </div>
        <div class="text-muted small mt-2 mt-md-0">
            Periode: {{ $dateRangeLabel }}
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Filtres</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('rapports.etats') }}">
                <div class="row">
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="etat_type">Etat</label>
                            <select name="etat_type" id="etat_type" class="form-control">
                                <option value="subscriptions" {{ $stateType === 'subscriptions' ? 'selected' : '' }}>Subscriptions</option>
                                <option value="paiements" {{ $stateType === 'paiements' ? 'selected' : '' }}>Paiements</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="date_field">Date sur</label>
                            <select name="date_field" id="date_field" class="form-control" {{ $stateType === 'paiements' ? 'disabled' : '' }}>
                                <option value="date_debut" {{ ($filters['date_field'] ?? 'date_debut') === 'date_debut' ? 'selected' : '' }}>Date debut</option>
                                <option value="date_fin" {{ ($filters['date_field'] ?? '') === 'date_fin' ? 'selected' : '' }}>Date fin</option>
                            </select>
                            @if($stateType === 'paiements')
                                <input type="hidden" name="date_field" value="date_paiement">
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="date_from">Date debut</label>
                            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="form-group">
                            <label for="date_to">Date fin</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-8">
                        <div class="form-group">
                            <label for="service_id">Service</label>
                            <select name="service_id" id="service_id" class="form-control">
                                <option value="">Tous les services</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}" {{ (string) ($filters['service_id'] ?? '') === (string) $service->id ? 'selected' : '' }}>
                                        {{ $service->nom }} ({{ $service->type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-md-4 subscription-filter">
                        <div class="form-group">
                            <label for="statut">Statut subscription</label>
                            <select name="statut" id="statut" class="form-control">
                                <option value="">Tous</option>
                                <option value="actif" {{ ($filters['statut'] ?? '') === 'actif' ? 'selected' : '' }}>Actif</option>
                                <option value="expire" {{ ($filters['statut'] ?? '') === 'expire' ? 'selected' : '' }}>Expire</option>
                                <option value="suspendu" {{ ($filters['statut'] ?? '') === 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 subscription-filter">
                        <div class="form-group">
                            <label for="type_abonnement">Type subscription</label>
                            <select name="type_abonnement" id="type_abonnement" class="form-control">
                                <option value="">Tous</option>
                                <option value="mensuel" {{ ($filters['type_abonnement'] ?? '') === 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                                <option value="trimestriel" {{ ($filters['type_abonnement'] ?? '') === 'trimestriel' ? 'selected' : '' }}>Trimestriel</option>
                                <option value="annuel" {{ ($filters['type_abonnement'] ?? '') === 'annuel' ? 'selected' : '' }}>Annuel</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 payment-filter">
                        <div class="form-group">
                            <label for="mode_paiement">Mode paiement</label>
                            <select name="mode_paiement" id="mode_paiement" class="form-control">
                                <option value="">Tous</option>
                                <option value="especes" {{ ($filters['mode_paiement'] ?? '') === 'especes' ? 'selected' : '' }}>Especes</option>
                                <option value="carte" {{ ($filters['mode_paiement'] ?? '') === 'carte' ? 'selected' : '' }}>Carte</option>
                                <option value="cheque" {{ ($filters['mode_paiement'] ?? '') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="virement" {{ ($filters['mode_paiement'] ?? '') === 'virement' ? 'selected' : '' }}>Virement</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-12 d-flex align-items-end">
                        <div class="form-group mb-0 w-100">
                            <button type="submit" class="btn btn-primary mr-2">Afficher</button>
                            <a href="{{ route('rapports.etats.pdf', request()->query()) }}" class="btn btn-danger mr-2">Exporter PDF</a>
                            <a href="{{ route('rapports.etats') }}" class="btn btn-default">Reinitialiser</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach($cards as $card)
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $card['value'] }}</h3>
                        <p>{{ $card['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-body py-4">
            <p class="text-muted mb-2">{{ $description }}</p>
            <p class="mb-0">Had page kat3tik ghir synthese. Ila bghiti details kamlin, klik 3la <strong>Exporter PDF</strong>.</p>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.small-box,
.card {
    border-radius: 12px;
}
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('etat_type');
    const dateField = document.getElementById('date_field');
    const subscriptionFilters = document.querySelectorAll('.subscription-filter');
    const paymentFilters = document.querySelectorAll('.payment-filter');

    function toggleFilters() {
        const isPayment = typeSelect.value === 'paiements';

        subscriptionFilters.forEach((item) => {
            item.style.display = isPayment ? 'none' : '';
            item.querySelectorAll('select, input').forEach((field) => {
                field.disabled = isPayment;
            });
        });

        paymentFilters.forEach((item) => {
            item.style.display = isPayment ? '' : 'none';
            item.querySelectorAll('select, input').forEach((field) => {
                field.disabled = !isPayment;
            });
        });

        if (dateField) {
            dateField.disabled = isPayment;
        }
    }

    typeSelect.addEventListener('change', toggleFilters);
    toggleFilters();
});
</script>
@stop
