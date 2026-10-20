<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Utilisateur;
use App\Models\Entreprise;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer ou créer l'entreprise par défaut
        $entreprise = Entreprise::first();
        if (!$entreprise) {
            $entreprise = Entreprise::create([
    'nom' => 'HaRaFact',
    'email' => 'contact@harafact.com',
    'adresse_ligne1' => 'Niamey',
    'ville' => 'Niamey',
    'pays' => 'NE',
]);
        }

        $users = [
            [
                'entreprise_id' => $entreprise->id,
                'nom'          => 'Hamidou',
                'prenom'       => 'Rabiou',
                'email'        => 'dg@harafact.com',
                'mot_de_passe' => Hash::make('dg1234'),
                'role'         => 'DG',
                'est_actif'    => true,
            ],
            [
                'entreprise_id' => $entreprise->id,
                'nom'          => 'Admin',
                'prenom'       => 'HaRaFact',
                'email'        => 'admin@harafact.com',
                'mot_de_passe' => Hash::make('admin1234'),
                'role'         => 'Admin',
                'est_actif'    => true,
            ],
            [
                'entreprise_id' => $entreprise->id,
                'nom'          => 'Moussa',
                'prenom'       => 'Comptable',
                'email'        => 'comptable@harafact.com',
                'mot_de_passe' => Hash::make('compta1234'),
                'role'         => 'Comptable',
                'est_actif'    => true,
            ],
             [
    'entreprise_id' => $entreprise->id,
    'nom'           => 'Haoua',
    'prenom'        => 'Employé',
    'email'         => 'employe@harafact.com',
    'mot_de_passe'  => Hash::make('employe1234'),
    'role'          => 'Employe',
    'est_actif'     => true,
],
           
        ];

        foreach ($users as $data) {
            Utilisateur::updateOrCreate(['email' => $data['email']], $data);
        }

        $this->command->info('✅ 4 utilisateurs créés :');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['DG',        'dg@harafact.com',        'dg1234'],
                ['Admin',     'admin@harafact.com',     'admin1234'],
                ['Comptable', 'comptable@harafact.com', 'compta1234'],
                ['Employé',   'employe@harafact.com',   'employe1234'],
            ]
        );
    }
}