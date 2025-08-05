<?php

use App\Core\Router;


// Pages classiques avec rendu HTML
Router::addWebRoute('GET', '/', PAGES_PATH . 'home.php');
Router::addWebRoute('GET', '/connexion', PAGES_PATH . 'loginForm.php');
Router::addWebRoute('GET', '/inscription', PAGES_PATH . 'registrationForm.php');
Router::addWebRoute('GET', '/publier', PAGES_PATH . 'postForm.php');
Router::addWebRoute('GET', '/mentions-legales', PAGES_PATH . 'legaleNotice.php');
Router::addWebRoute('GET', '/faq', PAGES_PATH . 'faq.php');
Router::addWebRoute('GET', '/conditions-generales-ventes', PAGES_PATH . 'gcs.php');
Router::addWebRoute('GET', '/plan-du-site', PAGES_PATH . 'siteMap.php');
Router::addWebRoute('GET', '/formulaire-contact', PAGES_PATH . 'contactForm.php');

// Exemple pour une soumission de formulaire (méthode POST)
Router::addWebRoute('POST', '/connexion', PAGES_PATH . 'loginHandler.php'); // Un fichier qui traite la connexion