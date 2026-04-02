@extends('adminlte::page')

@section('title', 'Détails Coach')

@section('content_header')
    <h1>Détails du Coach</h1>
@stop

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h3>{{ $coach->nom }} {{ $coach->prenom }}</h3>
        </div>
        <div class="card-body">
            <p><strong>Téléphone:</strong> {{ $coach->telephone }}</p>
            <p><strong>Email:</strong> {{ $coach->email }}</p>
            <p><strong>Spécialité:</strong> {{ $coach->specialite }}</p>
            <p><strong>Salaire:</strong> {{ $coach->salaire }} DH</p>
            <p><strong>Date d'embauche:</strong> {{ $coach->date_embauche }}</p>
            <p><strong>Statut:</strong> {{ $coach->statut }}</p>
        </div>
        <div class="card-footer">
            <a href="{{ route('coaches.edit', $coach) }}" class="btn btn-warning">Modifier</a>
            <a href="{{ route('coaches.index') }}" class="btn btn-secondary">Retour</a>
        </div>
    </div>
</div>
@stop
