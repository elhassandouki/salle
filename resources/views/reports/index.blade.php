@extends('adminlte::page')
@section('title', 'Rapports')

@section('content_header')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Rapports</h1>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group">
                <a href="{{ route('dashboard.rapports') }}" class="btn btn-sm {{ $reportType === 'general' ? 'btn-primary' : 'btn-default' }}">General</a>
                <a href="{{ route('rapports.financier') }}" class="btn btn-sm {{ $reportType === 'financier' ? 'btn-primary' : 'btn-default' }}">Financier</a>
                <a href="{{ route('rapports.frequentation') }}" class="btn btn-sm {{ $reportType === 'frequentation' ? 'btn-primary' : 'btn-default' }}">Frequentation</a>
                <a href="{{ route('rapports.assurances') }}" class="btn btn-sm {{ $reportType === 'assurances' ? 'btn-primary' : 'btn-default' }}">Assurances</a>
                <a href="{{ route('rapports.subscriptions') }}" class="btn btn-sm {{ $reportType === 'subscriptions' ? 'btn-primary' : 'btn-default' }}">Subscriptions</a>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach(($reportCards[$reportType] ?? []) as $card)
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

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Paiements recents</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm">
                        <thead><tr><th>Abonne</th><th>Service</th><th>Montant</th><th>Date</th></tr></thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->subscription->abonne->nom ?? '' }} {{ $payment->subscription->abonne->prenom ?? '' }}</td>
                                    <td>{{ $payment->subscription->service->nom ?? '-' }}</td>
                                    <td>{{ number_format((float) $payment->montant, 2) }} DH</td>
                                    <td>{{ optional($payment->date_paiement)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Aucune donnee</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Subscriptions proches de la fin</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm">
                        <thead><tr><th>Abonne</th><th>Service</th><th>Fin</th><th>Statut</th></tr></thead>
                        <tbody>
                            @forelse($expiringSubscriptions as $subscription)
                                <tr>
                                    <td>{{ $subscription->abonne->nom ?? '' }} {{ $subscription->abonne->prenom ?? '' }}</td>
                                    <td>{{ $subscription->service->nom ?? '-' }}</td>
                                    <td>{{ optional($subscription->date_fin)->format('d/m/Y') }}</td>
                                    <td>{{ $subscription->statut }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Aucune donnee</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($reportType === 'assurances')
        <div class="card">
            <div class="card-header"><h3 class="card-title">Reclamations recentes</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead><tr><th>Abonne</th><th>Compagnie</th><th>Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                        @forelse($recentClaims as $claim)
                            <tr>
                                <td>{{ $claim->abonne->nom ?? '' }} {{ $claim->abonne->prenom ?? '' }}</td>
                                <td>{{ $claim->company->nom ?? '-' }}</td>
                                <td>{{ number_format((float) $claim->montant_total, 2) }} DH</td>
                                <td>{{ $claim->statut }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Aucune donnee</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@stop
