@extends('layouts.app')
@section('title','Créer utilisateur')

@section('content')
<div style="max-width:500px;margin:20px auto;">
  <h1>Créer un nouvel utilisateur</h1>
  
  <form method="POST" action="{{ route('utilisateurs.store') }}">
    @csrf
    
    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Nom</strong></label>
      <input type="text" name="nom" required style="width:100%;padding:8px;border:1px solid #ddd">
    </div>

    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Prénom</strong></label>
      <input type="text" name="prenom" required style="width:100%;padding:8px;border:1px solid #ddd">
    </div>

    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Email</strong></label>
      <input type="email" name="email" required style="width:100%;padding:8px;border:1px solid #ddd">
    </div>

    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Rôle</strong></label>
      <select name="role" required style="width:100%;padding:8px;border:1px solid #ddd">
        <option value="">-- Sélectionner --</option>
        <option value="DG">DG</option>
        <option value="Admin">Admin</option>
        <option value="Employe">Employé</option>
        <option value="Comptable">Comptable</option>
      </select>
    </div>

    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Mot de passe</strong></label>
      <input type="password" name="mot_de_passe" required style="width:100%;padding:8px;border:1px solid #ddd">
    </div>

    <div style="margin-bottom:15px">
      <label style="display:block;margin-bottom:5px"><strong>Confirmer mot de passe</strong></label>
      <input type="password" name="mot_de_passe_confirmation" required style="width:100%;padding:8px;border:1px solid #ddd">
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit" style="padding:10px 20px;background:#2E5FA3;color:white;border:none;cursor:pointer">Créer</button>
      <a href="{{ route('utilisateurs.index') }}" style="padding:10px 20px;background:#f0f0f0;border:none;cursor:pointer;text-decoration:none">Annuler</a>
    </div>
  </form>
</div>
@endsection