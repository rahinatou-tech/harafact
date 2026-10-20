<?php
namespace App\Http\Controllers;

use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    public function index()
    {
        $utilisateurs = Utilisateur::latest()->paginate(15);
        return view('utilisateurs.index', compact('utilisateurs'));
    }

    public function create()
    {
        return view('utilisateurs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'     => 'required|string|max:255',
            'prenom'  => 'required|string|max:255',
            'email'   => 'required|email|unique:utilisateurs,email',
            'role'    => 'required|in:DG,Admin,Employe,Comptable',
            'mot_de_passe' => 'required|min:8|confirmed',
        ]);
        $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
        $data['entreprise_id'] = auth()->user()->entreprise_id;
        $data['est_actif'] = true;
        
        Utilisateur::create($data);
        return redirect()->route('utilisateurs.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(Utilisateur $utilisateur)
    {
        return view('utilisateurs.edit', compact('utilisateur'));
    }

    public function update(Request $request, Utilisateur $utilisateur)
    {
        $data = $request->validate([
            'nom'    => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email'  => 'required|email|unique:utilisateurs,email,'.$utilisateur->id,
            'role'   => 'required|in:DG,Admin,Employe,Comptable',
            'est_actif' => 'boolean',
        ]);
        
        if($request->filled('mot_de_passe')) {
            $request->validate(['mot_de_passe' => 'min:8|confirmed']);
            $data['mot_de_passe'] = Hash::make($request->mot_de_passe);
        }
        
        $utilisateur->update($data);
        return redirect()->route('utilisateurs.index')->with('success', 'Utilisateur modifié.');
    }

    public function destroy(Utilisateur $utilisateur)
    {
        if($utilisateur->id === auth()->id()) {
            return back()->with('error', 'Impossible de supprimer votre propre compte.');
        }
        $utilisateur->delete();
        return redirect()->route('utilisateurs.index')->with('success', 'Utilisateur supprimé.');
    }
}