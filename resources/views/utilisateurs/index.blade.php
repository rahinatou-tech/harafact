@extends('layouts.app')
@section('title','Utilisateurs')
@section('page-title','Gestion des utilisateurs')

@section('topbar-actions')
<a href="{{ route('utilisateurs.create') }}" class="tb-btn">+ Ajouter</a>
@endsection

@section('content')

<div class="card">
  <div class="card-header">
    <div class="card-title">Liste des utilisateurs</div>
  </div>

  <table>
    <thead>
      <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>
    </thead>
    <tbody>
      @forelse($utilisateurs as $u)
      <tr>
        <td>{{ $u->nom }} {{ $u->prenom }}</td>
        <td>{{ $u->email }}</td>
        <td><span class="tag">{{ $u->role }}</span></td>
        <td>{{ $u->est_actif ? '✓ Actif' : '✗ Inactif' }}</td>
        <td>
          <a href="{{ route('utilisateurs.edit', $u) }}" class="btn-icon">✏️</a>
          <form method="POST" action="{{ route('utilisateurs.destroy', $u) }}" onsubmit="return confirm('Supprimer ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-icon del">🗑️</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:20px">Aucun utilisateur</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection