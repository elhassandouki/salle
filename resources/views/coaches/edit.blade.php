@extends('adminlte::page')

@section('title', 'Modifier Coach')

@section('content_header')
    <h1>Modifier Coach</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('coaches.update', $coach) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" name="nom" class="form-control" value="{{ $coach->nom }}" required>
        </div>
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" class="form-control" value="{{ $coach->prenom }}" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="{{ $coach->telephone }}">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $coach->email }}">
        </div>
        <div class="form-group">
            <label for="specialite">Spécialité</label>
            <input type="text" name="specialite" class="form-control" value="{{ $coach->specialite }}">
        </div>
        <div class="form-group">
            <label for="salaire">Salaire</label>
            <input type="number" step="0.01" name="salaire" class="form-control" value="{{ $coach->salaire }}">
        </div>
        <div class="form-group">
            <label for="date_embauche">Date d'embauche</label>
            <input type="date" name="date_embauche" class="form-control" value="{{ $coach->date_embauche }}">
        </div>
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" class="form-control">
                <option value="actif" {{ $coach->statut == 'actif' ? 'selected' : '' }}>Actif</option>
                <option value="inactif" {{ $coach->statut == 'inactif' ? 'selected' : '' }}>Inactif</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
@stop
