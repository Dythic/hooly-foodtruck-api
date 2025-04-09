# Hooly Foodtruck API

## Gestion des Réservations de Foodtrucks

### Contexte du Projet

L'API Hooly Foodtruck permet aux foodtrucks de réserver des emplacements sur les campus de Hooly, avec des règles de gestion spécifiques.

### Campus Disponibles

- **Paris**
  - 7 emplacements disponibles
  - 6 emplacements le vendredi

- **Lyon**
  - 5 emplacements disponibles
  - 4 emplacements le lundi

### Règles Métier

1. **Réservations**
   - Maximum 1 emplacement par campus par semaine
   - Un foodtruck ne peut pas être sur deux campus le même jour
   - Réservations minimum 1 jour à l'avance
   - Pas de réservation pour le jour même ou une date passée

### Fonctionnalités

#### Gestion des Foodtrucks
- Enregistrement d'un nouveau foodtruck
- Consultation de la liste des foodtrucks

#### Gestion des Réservations
- Création de réservation
- Annulation de réservation
- Consultation des réservations par campus et date

#### Disponibilités
- Affichage des créneaux disponibles
- Vérification dynamique des slots

#### Système de Rappels
- Envoi automatique d'emails à 18h
- Informations sur les réservations du lendemain

### Technologies Utilisées

- **Langage** : PHP 8.4
- **Framework** : Symfony 7.2
- **Base de données** : MySQL/MariaDB
- **Documentation API** : OpenAPI/Swagger
- **Gestion des emails** : Symfony Mailer

### Prérequis

- PHP 8.4+
- Composer
- MySQL/MariaDB
- Symfony CLI

### Installation

1. Cloner le dépôt
```bash
git clone [URL_DU_DEPOT]
cd hooly-foodtruck-api
```

2. Installer les dépendances
```bash
composer install
```

3. Configuration de la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 🧪 Endpoints API

#### Foodtrucks
- `POST /api/foodtrucks` : Créer un foodtruck
- `GET /api/foodtrucks` : Lister les foodtrucks

#### Réservations
- `POST /api/reservations` : Créer une réservation
- `DELETE /api/reservations/{id}` : Annuler une réservation
- `GET /api/reservations` : Consulter les réservations

#### Campus
- `GET /api/campus` : Lister les campus
- `GET /api/campus/{id}/available-slots` : Vérifier les créneaux disponibles

### 📧 Système de Rappels

Commande pour envoyer les rappels :
```bash
php bin/console app:send-reservation-reminders
```

### 🔒 Sécurité

- Validation des entrées
- Protection contre les injections
- Contraintes métier intégrées

### 🔍 Documentation API

Accéder à Swagger UI :
```
/api/doc
```

### 🛠 Développement

- Mode développement : `symfony server:start`
- Vérifier les logs : `var/log/dev.log`

### 📝 Notes Importantes

- Respecter les règles métier de réservation
- Les emails sont interceptés en développement
- Utiliser MailHog recommandé pour tester les emails

