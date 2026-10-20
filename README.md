# HaRaFact — Guide d'installation

## Prérequis
- PHP >= 8.2
- Composer
- MySQL 8.0+
- Node.js >= 18

---

## 1. Créer le projet Laravel

```bash
composer create-project laravel/laravel harafact
cd harafact
```

## 2. Installer les dépendances

```bash
# Authentification API (Sanctum)
composer require laravel/sanctum

# Génération PDF
composer require barryvdh/laravel-dompdf

# Publication Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Remplir `.env` :
```
DB_DATABASE=harafact
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

## 4. Créer la base de données MySQL

```sql
CREATE DATABASE harafact CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 5. Copier les fichiers générés

Copier dans les bons dossiers :

```
database/migrations/  → tous les fichiers 2025_01_01_*.php
database/seeders/DatabaseSeeder.php
app/Models/           → Client.php, Document.php, DocumentLigne.php,
                        Paiement.php, Produit.php, User.php
app/Http/Controllers/Api/  → tous les *Controller.php
app/Http/Middleware/  → CheckStatut.php, CheckPermission.php
routes/api.php
```

## 6. Enregistrer les middlewares

Dans `app/Http/Kernel.php`, ajouter dans `$middlewareAliases` :

```php
'check.statut'  => \App\Http\Middleware\CheckStatut::class,
'can.access'    => \App\Http\Middleware\CheckPermission::class,
```

## 7. Lancer les migrations et le seeder

```bash
php artisan migrate
php artisan db:seed
```

## 8. Démarrer le serveur

```bash
php artisan serve
# → http://localhost:8000
```

---

## Comptes de démo

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@harafact.ne | harafact123 | Directeur Général |
| boubacar@harafact.ne | harafact123 | Administrateur |
| compta@harafact.ne | harafact123 | Comptable |

---

## Endpoints API principaux

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | /api/auth/login | Connexion |
| GET | /api/dashboard | Stats tableau de bord |
| GET/POST | /api/clients | Liste / Créer client |
| GET/POST | /api/documents | Liste / Créer document |
| POST | /api/documents/{id}/envoyer | Envoyer par WhatsApp/Email |
| GET/POST | /api/paiements | Liste / Enregistrer paiement |
| GET/POST | /api/produits | Liste / Créer produit |
| GET/PUT | /api/config | Lire / Sauvegarder config |

---

## Structure des dossiers

```
harafact/
├── app/
│   ├── Models/
│   │   ├── Client.php
│   │   ├── Document.php
│   │   ├── DocumentLigne.php
│   │   ├── Paiement.php
│   │   ├── Produit.php
│   │   └── User.php
│   └── Http/
│       ├── Controllers/Api/
│       │   ├── AuthController.php
│       │   ├── DashboardController.php
│       │   ├── ClientController.php
│       │   ├── DocumentController.php
│       │   ├── PaiementController.php
│       │   ├── ProduitController.php
│       │   ├── UserController.php
│       │   └── ConfigurationController.php
│       └── Middleware/
│           ├── CheckStatut.php
│           └── CheckPermission.php
├── database/
│   ├── migrations/
│   │   ├── ..._add_fields_to_users_table.php
│   │   ├── ..._create_clients_table.php
│   │   ├── ..._create_produits_table.php
│   │   ├── ..._create_documents_table.php
│   │   ├── ..._create_document_lignes_table.php
│   │   ├── ..._create_paiements_table.php
│   │   └── ..._create_config_taches_envois_tables.php
│   └── seeders/DatabaseSeeder.php
└── routes/
    └── api.php
```
