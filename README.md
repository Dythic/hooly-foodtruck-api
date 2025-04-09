Hooly Foodtruck API
Une API permettant aux foodtrucks de réserver des emplacements sur les campus de Hooly et aux employés de consulter les disponibilités.
Spécifications techniques

PHP 8.4
Symfony 7.2
Base de données: MySQL/PostgreSQL (configurable)
Documentation API: OpenAPI/Swagger

Fonctionnalités

Gestion des Foodtrucks (création, consultation)
Gestion des Réservations (création, annulation, consultation)
Consultation des Disponibilités par campus et par date
Envoi automatique d'emails de rappel la veille des réservations

Installation

Cloner le dépôt

bashgit clone https://github.com/yourusername/hooly-foodtruck-api.git
cd hooly-foodtruck-api

Installer les dépendances

bashcomposer install

Configurer la base de données dans le fichier .env

DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"

Configurer le mailer dans le fichier .env

MAILER_DSN=smtp://user:pass@smtp.example.com:25

Créer la base de données et effectuer les migrations

bashphp bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

Charger les données initiales

bashphp bin/console doctrine:fixtures:load

Lancer le serveur de développement

bashsymfony server:start
Architecture et choix techniques
Structure du projet
Le projet suit l'architecture MVC de Symfony avec :

Controllers : gestion des requêtes HTTP
Entities : modèles de données
Repositories : accès aux données
Services : logique métier
Commands : commandes console pour les tâches planifiées

Base de données

La base de données utilise Doctrine ORM pour la gestion des entités
Les relations entre entités sont :

OneToMany entre Campus et Reservation
OneToMany entre Foodtruck et Reservation



Sécurité

Validation des entrées utilisateur
Protection contre les injections SQL via Doctrine
Utilisation des types de données appropriés

Système d'emails

Utilisation de Symfony Mailer pour l'envoi des emails
Command pour l'envoi des rappels, pouvant être planifiée via CRON ou Symfony Scheduler

Documentation API
La documentation de l'API est disponible à l'adresse /api/doc une fois le serveur lancé.
Principales routes :

GET /api/foodtrucks : Liste des foodtrucks
POST /api/foodtrucks : Création d'un foodtruck
GET /api/reservations : Liste des réservations par campus et date
POST /api/reservations : Création d'une réservation
DELETE /api/reservations/{id} : Annulation d'une reservation