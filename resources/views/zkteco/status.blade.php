@extends('adminlte::page')
@section('title', 'Statut ZKTeco')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h1>Statut ZKTeco</h1>
            <p class="text-muted mb-0">Cette page confirme que le point d'entree ZKTeco est bien branche dans l'application.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $deviceName }}</h3>
                    <p>Appareil</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $todayEntries }}</h3>
                    <p>Entrees aujourd hui</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalAbonnes }}</h3>
                    <p>Abonnes</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $lastSync ?? 'N/A' }}</h3>
                    <p>Dernier pointage</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Etat de la connexion</h3></div>
        <div class="card-body">
            <p><strong>Statut:</strong> {{ $status }}</p>
            <p><strong>Endpoint API:</strong> <code>{{ $syncEndpoint }}</code></p>
            <p class="mb-0">Si vous utilisez un SDK ou un bridge ZKTeco, il faut encore connecter ce endpoint a votre appareil ou service d'import.</p>
        </div>
    </div>
</div>
@stop
