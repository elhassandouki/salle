@extends('adminlte::page')

@section('title', 'Ajouter Coach')

@section('content_header')
    <h1>Ajouter un Coach</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('coaches.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" name="nom" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="text" name="telephone" class="form-control">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
            <label for="specialite">Spécialité</label>
            <input type="text" name="specialite" class="form-control">
        </div>
        <div class="form-group">
            <label for="salaire">Salaire</label>
            <input type="number" step="0.01" name="salaire" class="form-control">
        </div>
        <div class="form-group">
            <label for="date_embauche">Date d'embauche</label>
            <input type="date" name="date_embauche" class="form-control">
        </div>
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" class="form-control">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
    </form>
</div>
@stop
