<?php
// ============================================================
// FICHIER : database/seeders/DatabaseSeeder.php
// Remplace TOUT le contenu de ce fichier
// Lance avec : php artisan db:seed
// ============================================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Entreprise;
use App\Models\Utilisateur;
use App\Models\GroupeTaxe;
use App\Models\Client;
use App\Models\ProduitService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET session_replication_role = replica;');
        DB::table('journal_audit')->truncate();
        DB::table('communications')->truncate();
        DB::table('paiements')->truncate();
        DB::table('lignes_documents')->truncate();
        DB::table('configs_recurrence')->truncate();
        DB::table('factures')->truncate();
        DB::table('devis')->truncate();
        DB::table('produits_services')->truncate();
        DB::table('clients')->truncate();
        DB::table('groupes_taxes')->truncate();
        DB::table('utilisateurs')->truncate();
        DB::table('entreprises')->truncate();
       DB::statement('SET session_replication_role = DEFAULT;');

        // ══════════════════════════════════════════════════════
        // 1. ENTREPRISE
        // ══════════════════════════════════════════════════════
        $entreprise = Entreprise::create([
            'nom'                    => 'HaRa Tech Solutions',
            'forme_juridique'        => 'SARL',
            'numero_fiscal'          => 'NIF-2025-NE-00001',
            'numero_tva'             => 'TVA-NE-0001',
            'adresse_ligne1'         => 'Rue des Bâtisseurs, Plateau',
            'ville'                  => 'Niamey',
            'pays'                   => 'NE',
            'telephone'              => '+227 20 00 00 01',
            'email'                  => 'contact@haratech.ne',
            'site_web'               => 'https://haratech.ne',
            'devise_defaut'          => 'XOF',
            'delai_paiement_jours'   => 30,
            'fuseau_horaire'         => 'Africa/Niamey',
            'type_activite'          => 'les_deux',
            'pied_page_facture'      => 'HaRa Tech Solutions — SARL au capital de 5 000 000 XOF — NIF: NIF-2025-NE-00001 — Niamey, Niger',
            'coordonnees_bancaires'  => [
                'banque' => 'BIA Niger',
                'iban'   => 'NE00 BNIA 0000 0000 0000 0001',
                'bic'    => 'BNIANE01',
            ],
            // Paramètres notifications
            'notif_jours_avant_echeance'  => [7, 3, 1],
            'notif_jours_apres_echeance'  => [1, 7, 15],
            'notif_envoi_auto_facture'    => true,
            'notif_envoi_recu_paiement'   => true,
            'notif_envoi_auto_devis'      => false,
            // Séquences (toutes à 0 pour repartir)
            'sequences_numerotation' => [
                'FSI' => 0,
                'PRO' => 0,
                'DEV' => 0,
                'AVO' => 0,
                'ACP' => 0,
                'SOL' => 0,
                'REC' => 0,
            ],
            'est_actif' => true,
        ]);

        // ══════════════════════════════════════════════════════
        // 2. UTILISATEURS
        // ══════════════════════════════════════════════════════

        // DG — mot de passe : Admin1234
        $dg = Utilisateur::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'Mahamane',
            'prenom'        => 'Sani',
            'email'         => 'dg@haratech.ne',
            'mot_de_passe'  => Hash::make('Admin1234'),
            'role'          => 'DG',
            'est_actif'     => true,
        ]);

        // Admin — mot de passe : Admin1234
        $admin = Utilisateur::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'Oumarou',
            'prenom'        => 'Aïssatou',
            'email'         => 'admin@haratech.ne',
            'mot_de_passe'  => Hash::make('Admin1234'),
            'role'          => 'Admin',
            'est_actif'     => true,
            'cree_par'      => $dg->id,
        ]);

        // Comptable — mot de passe : Admin1234
        Utilisateur::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'Ibrahim',
            'prenom'        => 'Fatima',
            'email'         => 'comptable@haratech.ne',
            'mot_de_passe'  => Hash::make('Admin1234'),
            'role'          => 'Comptable',
            'est_actif'     => true,
            'cree_par'      => $dg->id,
        ]);

        // ══════════════════════════════════════════════════════
        // 3. GROUPES DE TAXES
        // ══════════════════════════════════════════════════════

        // TVA standard Niger 19%
        $taxeStandard = GroupeTaxe::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'TVA Niger standard',
            'description'   => 'TVA applicable sur les prestations et marchandises au Niger',
            'composantes'   => [
                [
                    'nom'            => 'TVA',
                    'code'           => 'TVA19',
                    'taux'           => 0.19,
                    's_applique_sur' => 'ht',
                    'ordre'          => 1,
                    'code_comptable' => '4431',
                ]
            ],
            'taux_total'  => 0.19,
            'est_defaut'  => true,
            'est_actif'   => true,
        ]);

        // Exonéré
        $taxeExo = GroupeTaxe::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'Exonéré de TVA',
            'description'   => 'Opérations exonérées ou hors champ TVA',
            'composantes'   => [],
            'taux_total'    => 0.00,
            'est_defaut'    => false,
            'est_actif'     => true,
        ]);

        // TVA + Taxe spéciale (exemple)
        GroupeTaxe::create([
            'entreprise_id' => $entreprise->id,
            'nom'           => 'TVA + Taxe spéciale',
            'description'   => 'TVA 19% + Taxe spéciale 2% sur certaines importations',
            'composantes'   => [
                [
                    'nom'            => 'TVA',
                    'code'           => 'TVA19',
                    'taux'           => 0.19,
                    's_applique_sur' => 'ht',
                    'ordre'          => 1,
                    'code_comptable' => '4431',
                ],
                [
                    'nom'            => 'Taxe spéciale',
                    'code'           => 'TS2',
                    'taux'           => 0.02,
                    's_applique_sur' => 'ht',
                    'ordre'          => 2,
                    'code_comptable' => '4432',
                ],
            ],
            'taux_total'  => 0.21,
            'est_defaut'  => false,
            'est_actif'   => true,
        ]);

        // ══════════════════════════════════════════════════════
        // 4. CLIENTS
        // ══════════════════════════════════════════════════════
        $clients = [
            [
                'type_client'     => 'entreprise',
                'code_client'     => 'CLI-0001',
                'raison_sociale'  => 'NIGER TELECOM SA',
                'prenom'          => 'Moussa',
                'nom'             => 'Abdou',
                'email'           => 'facturation@nigertel.ne',
                'telephone'       => '+227 20 72 00 00',
                'adresse_ligne1'  => 'Boulevard du 15 Avril, Centre-ville',
                'ville'           => 'Niamey',
                'pays'            => 'NE',
                'numero_fiscal'   => 'NIF-NT-0001',
                'groupe_taxes_id' => $taxeStandard->id,
            ],
            [
                'type_client'     => 'entreprise',
                'code_client'     => 'CLI-0002',
                'raison_sociale'  => 'Orange Niger',
                'prenom'          => 'Mariama',
                'nom'             => 'Issaka',
                'email'           => 'compta@orange.ne',
                'telephone'       => '+227 20 73 00 00',
                'adresse_ligne1'  => 'Rue de l\'Aéroport, Plateau',
                'ville'           => 'Niamey',
                'pays'            => 'NE',
                'groupe_taxes_id' => $taxeStandard->id,
            ],
            [
                'type_client'     => 'particulier',
                'code_client'     => 'CLI-0003',
                'prenom'          => 'Ibrahim',
                'nom'             => 'Maïga',
                'email'           => 'i.maiga@gmail.com',
                'telephone'       => '+227 96 00 00 01',
                'adresse_ligne1'  => 'Quartier Yantala',
                'ville'           => 'Niamey',
                'pays'            => 'NE',
                'groupe_taxes_id' => $taxeStandard->id,
            ],
            [
                'type_client'     => 'entreprise',
                'code_client'     => 'CLI-0004',
                'raison_sociale'  => 'Ministère des Finances',
                'prenom'          => 'Sani',
                'nom'             => 'Oumarou',
                'email'           => 'daf@finances.gouv.ne',
                'telephone'       => '+227 20 72 29 99',
                'adresse_ligne1'  => 'Avenue de l\'Indépendance',
                'ville'           => 'Niamey',
                'pays'            => 'NE',
                'groupe_taxes_id' => $taxeExo->id, // État = exonéré
            ],
            [
                'type_client'     => 'entreprise',
                'code_client'     => 'CLI-0005',
                'raison_sociale'  => 'Société de Construction BTP Niger',
                'prenom'          => 'Ramatou',
                'nom'             => 'Amadou',
                'email'           => 'ramatou@btp-niger.ne',
                'telephone'       => '+227 96 11 22 33',
                'adresse_ligne1'  => 'Route de Tillabéri, Km 5',
                'ville'           => 'Niamey',
                'pays'            => 'NE',
                'groupe_taxes_id' => $taxeStandard->id,
            ],
        ];

        foreach ($clients as $data) {
            Client::create(array_merge($data, [
                'entreprise_id' => $entreprise->id,
                'est_actif'     => true,
                'cree_par'      => $admin->id,
            ]));
        }

        // ══════════════════════════════════════════════════════
        // 5. PRODUITS & SERVICES
        // ══════════════════════════════════════════════════════
        $produits = [
            // PRODUITS
            [
                'type'            => 'produit',
                'reference'       => 'PRD-001',
                'nom'             => 'Ordinateur Dell Inspiron 15',
                'description'     => 'PC portable Intel Core i5, 8 Go RAM, 512 Go SSD, Windows 11',
                'unite'           => 'unité',
                'prix_unitaire_ht'=> 381356,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '2183',
                'stock'           => 15,
            ],
            [
                'type'            => 'produit',
                'reference'       => 'PRD-002',
                'nom'             => 'Imprimante HP LaserJet Pro',
                'description'     => 'Imprimante laser monochrome, 30 ppm, réseau WiFi',
                'unite'           => 'unité',
                'prix_unitaire_ht'=> 156780,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '2183',
                'stock'           => 8,
            ],
            [
                'type'            => 'produit',
                'reference'       => 'PRD-003',
                'nom'             => 'Switch HP Aruba 24 ports',
                'description'     => 'Switch manageable 24 ports Gigabit, rackable 1U',
                'unite'           => 'unité',
                'prix_unitaire_ht'=> 420000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '2183',
                'stock'           => 5,
            ],
            [
                'type'            => 'produit',
                'reference'       => 'PRD-004',
                'nom'             => 'Ramette papier A4 80g',
                'description'     => 'Ramette 500 feuilles, papier blanc 80g/m²',
                'unite'           => 'ramette',
                'prix_unitaire_ht'=> 4200,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '6011',
                'stock'           => 200,
            ],
            // SERVICES
            [
                'type'            => 'service',
                'reference'       => 'SRV-001',
                'nom'             => 'Maintenance informatique (contrat annuel)',
                'description'     => 'Maintenance préventive et curative de tout le parc informatique — forfait annuel',
                'unite'           => 'forfait',
                'prix_unitaire_ht'=> 350000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '706',
            ],
            [
                'type'            => 'service',
                'reference'       => 'SRV-002',
                'nom'             => 'Consulting IT (journée)',
                'description'     => 'Prestation de conseil en informatique et transformation digitale — par journée',
                'unite'           => 'jour',
                'prix_unitaire_ht'=> 85000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '706',
            ],
            [
                'type'            => 'service',
                'reference'       => 'SRV-003',
                'nom'             => 'Formation bureautique (session)',
                'description'     => 'Formation Word, Excel, PowerPoint — groupe de 10 personnes maximum',
                'unite'           => 'session',
                'prix_unitaire_ht'=> 120000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '706',
            ],
            [
                'type'            => 'service',
                'reference'       => 'SRV-004',
                'nom'             => 'Développement site web (forfait)',
                'description'     => 'Conception et développement d\'un site web vitrine — inclut hébergement 1 an',
                'unite'           => 'forfait',
                'prix_unitaire_ht'=> 750000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '706',
            ],
            [
                'type'            => 'service',
                'reference'       => 'SRV-005',
                'nom'             => 'Infogérance mensuelle',
                'description'     => 'Gestion complète du système d\'information — tarif mensuel',
                'unite'           => 'mois',
                'prix_unitaire_ht'=> 200000,
                'groupe_taxes_id' => $taxeStandard->id,
                'code_comptable'  => '706',
            ],
        ];

        foreach ($produits as $data) {
            ProduitService::create(array_merge($data, [
                'entreprise_id' => $entreprise->id,
                'est_actif'     => true,
                'cree_par'      => $admin->id,
            ]));
        }

        $this->command->info('');
        $this->command->info('✅  Base de données initialisée avec succès !');
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  IDENTIFIANTS DE CONNEXION');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  DG         → dg@haratech.ne       | Admin1234');
        $this->command->info('  Admin      → admin@haratech.ne    | Admin1234');
        $this->command->info('  Comptable  → comptable@haratech.ne| Admin1234');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  Entreprise : HaRa Tech Solutions');
        $this->command->info('  Clients    : 5 clients créés');
        $this->command->info('  Produits   : 4 produits + 5 services');
        $this->command->info('  Taxes      : 3 groupes (TVA 19%, Exonéré, TVA+TS)');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
    }
}
