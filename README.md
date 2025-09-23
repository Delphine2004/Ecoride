# EcoRide

## 1. Description du projet

EcoRide est une application web de covoiturage qui privilégie l'utilisation de véhicule à faible empreinte écologique.
Elle permet aux utilisateurs de rechercher et de proposer des trajets ainsi que gérer leur compte et d'intéragir avec la plateforme selon leur rôle: passager, conducteur, employé ou admin.

---

## 2. Technologie utilisées

### Front-End

- **HTML, CSS et JavaScript Vanilla**
- Création d'une interface responsive et accessible.
- Mobile-first pour une meilleure expérience utilisateur et un meilleur référencement.

### Back-End

-**PHP**

- Langage adapté aux applications web dynamiques.
- Sans framework pour maitriser la logique métier et montrer ma compréhension des concepts de création.

### Gestionnaire de dépendance

- **Composer**
- Autoloading PSR-4 pour une structure claire.
- Bibliothèques utilisées :
- `vlucas/phpdotenv` : gestion des variables d’environnement.
- `phpmailer/phpmailer` : envoi d’emails sécurisé.
- `firebase/php-jwt` : génération et vérification des tokens JWT.
- `mongodb/mongodb` : gestion des commentaires et notes.
- `nikic/fast-route` : simplification du routage.

### Base de données

- **MySql** : Relations entre utilisateurs, trajets et voitures.
- **MongoDB** : Stockage des commentaires et des notes des conducteurs.

---

## 3. Installation

1. Cloner le dépôt :

   ```bash
    git clone https://github.com/Delphine2004/Ecoride.git
   ```

2. Installer les dépendances :

   ```bash
   composer install
   ```

3. Configurer le fichier .env pour les variables sensibles.

4. Lancer le serveur local avec php et MySql.

5. Accéder au projet via PHP server
   ```bash
   php -S localhost:8001 -t public
   ```

## 4. Fonctionnalités principales

- Recherche et filtrage de trajet
- Inscription et connexion sécurisée
- Gestion des trajets
- Gestion des rôles
- Espace employé pour gestion des trajets et des commentaires
- Espace admin pour gestion des utilisateurs et statistiques

## 5. Sécurité

- Validation front-end des formulaires avec Regex et messages clairs.
- Requêtes préparées PDO et filtrage des entrées pour éviter les injections SQL.
- Hashage des mots de passe (password_hash) et vérification des tokens JWT.
- Vérification des rôles avant chaque action sensible.

## 6. Environnement de travail

- IDE : VS Code avec extensions PHP Intelephense, Prettier, ESLint.
- Serveur local : WAMP (Apache, PHP, MySQL).
- Versionning : Git & GitHub.

## 7. Informations complémentaires

- Architecture MVC.
