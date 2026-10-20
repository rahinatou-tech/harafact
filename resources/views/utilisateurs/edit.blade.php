@extends('layouts.app')
@section('title','Modifier utilisateur')
@section('page-title','Modifier ' . $utilisateur->nom . ' ' . $utilisateur->prenom)

@section('content')

<div class="card" style="max-width:500px">
  <form method="POST" action="{{ route('utilisateurs.update', $utilisateur) }}">
    @csrf
    @method('PUT')
    
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="nom" required value="{{ old('nom', $utilisateur->nom) }}">
      @error('nom')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label>Prénom</label>
      <input type="text" name="prenom" required value="{{ old('prenom', $utilisateur->prenom) }}">
      @error('prenom')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required value="{{ old('email', $utilisateur->email) }}">
      @error('email')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label>Rôle</label>
      <select name="role" required>
        <option value="DG" {{ $utilisateur->role === 'DG' ? 'selected' : '' }}>DG</option>
        <option value="Admin" {{ $utilisateur->role === 'Admin' ? 'selected' : '' }}>Admin</option>
        <option value="Employe" {{ $utilisateur->role === 'Employe' ? 'selected' : '' }}>Employé</option>
        <option value="Comptable" {{ $utilisateur->role === 'Comptable' ? 'selected' : '' }}>Comptable</option>
      </select>
      @error('role')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label>Statut</label>
      <select name="est_actif">
        <option value="1" {{ $utilisateur->est_actif ? 'selected' : '' }}>✓ Actif</option>
        <option value="0" {{ !$utilisateur->est_actif ? 'selected' : '' }}>✗ Inactif</option>
      </select>
    </div>

    <div class="form-group">
      <label>Nouveau mot de passe (optionnel)</label>
      <input type="password" name="mot_de_passe">
      @error('mot_de_passe')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
      <label>Confirmer mot de passe</label>
      <input type="password" name="mot_de_passe_confirmation">
    </div>

    <div style="display:flex;gap:10px;margin-top:20px">
      <button type="submit" class="btn btn-primary">Modifier</button>
      <a href="{{ route('utilisateurs.index') }}" class="btn">Annuler</a>
    </div>
  </form>
</div>

@endsection