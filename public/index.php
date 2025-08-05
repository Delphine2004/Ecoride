<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"> <!-- A FAIRE pour SEO -->


  <!--Google Font-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@400;700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="./assets/css/style.css" />
  <link rel="stylesheet" href="./assets/css/header.css" />
  <link rel="stylesheet" href="./assets/css/footer.css" />
  <link rel="stylesheet" href="./assets/css/hero.css" />
  <link rel="stylesheet" href="./assets/css/info.css" />
  <link rel="stylesheet" href="./assets/css/form.css" />
  <link rel="stylesheet" href="./assets/css/dashboard.css" />

  <!-- Script   <script src="./assets/js/main.js" type="module"></script> -->

  <script src="./assets/js/Router/Router.js" type="module"></script>

  <!--Favicon-->
  <link href="/public/assets/img/logo/logo.webp" rel="icon" type="image/webp" />

  <title>Ecoride</title>
</head>

<body>

  <header id="header-banner">

    <!-- Navbar grand écran -->
    <div class="bigNav nav">

      <div class="logo">
        <!-- Logo avec retour page accueil-->
        <a href="/"><img src="./assets/img/logo/logo.webp" alt="Logo de la plateforme de covoiturage EcoRide"></a>
      </div>

      <nav>
        <ul>
          <!-- Rechercher -->
          <li><a href="#hero">Rechercher un trajet</a></li>
          <!-- Publier -->
          <li><a href="/publier">Publier un trajet</a></li>
          <!-- Espace client -->
          <li data-show="connected"><a href="/account">Mon compte</a></li>
          <!-- Se connecter -->
          <li data-show="disconnected"><a href="/connexion">Connexion</a></li>
          <!-- Se deconnecter -->
          <li data-show="connected"><a href="/deconnexion">Déconnexion</a></li>

        </ul>
      </nav>
    </div>

    <!-- NavBar petit écran -->

    <!-- VOIR POUR ICON MON COMPTE -->
    <div class="smallNav nav">
      <!-- Le logo est à la deuxième place -->
      <nav>
        <!-- Se connecter -->
        <div class="user-icon" data-show="disconnected">
          <a href="/connexion"><img src="./assets/img/icons/account_circle.png" alt="Espace utilisateur de la plateforme de covoiturage EcoRide"></a>
        </div>

        <!-- Se deconnecter - A MODIFIER-->
        <div class="user-icon" data-show="connected">
          <a href="/deconnexion"><img src="./assets/img/icons/account_circle.png" alt="Espace utilisateur de la plateforme de covoiturage EcoRide"></a>
        </div>

        <div class="logo">
          <!-- Logo avec retour page accueil-->
          <a href="/"><img src="./assets/img/logo/logo.webp" alt="Logo de la plateforme de covoiturage EcoRide"></a>
        </div>

        <div class="icons">
          <!-- Rechercher -->
          <a href="#hero"><img src="./assets/img/icons/search.png" alt="rechercher un trajet sur la plateforme de covoiturage EcoRide"></a>
          <!-- Publier -->
          <a href="/publier"><img src="./assets/img/icons/add_circle.png" alt="publier un trajet sur la plateforme de covoiturage EcoRide"></a>
        </div>

      </nav>
    </div>

  </header>

  <section id="hero">
    <div class="title">
      <h1>EcoRide</h1>
      <h2>Voyagez malin, voyagez durable.</h2>
    </div>
    <form id="search-form" method="POST" novalidate>
      <!-- sr-only = screen reader only -->
      <div class="big-screen small-screen">
        <div class="item">
          <label class="sr-only" for="departure-place">Ville de départ</label>
          <input
            class="input-form"
            id="departure-place"
            name="departure_place"
            type="text"
            data-type="onlytext"
            maxlength="25"
            placeholder="Ville de départ"
            required />
          <div class="error-message" id="error-departure-place"></div>
        </div>
        <div class="item">
          <label class="sr-only" for="arrival-place">Ville d'arrivée</label>
          <input
            class="input-form"
            id="arrival-place"
            name="arrival_place"
            type="text"
            data-type="onlytext"
            maxlength="25"
            placeholder="Ville d'arrivée"
            required />
          <div class="error-message" id="error-arrival-place"></div>
        </div>
        <div class="item">
          <label class="sr-only" for="departure-date">Date de départ</label>
          <input
            class="input-form"
            id="departure-date"
            name="departure_date"
            type="date"
            required />
          <div class="error-message" id="error-departure-date"></div>
        </div>
        <div class="item">
          <label class="sr-only" for="number-person">Nombre de personne</label>
          <input
            class="input-form"
            id="number-person"
            name="number_person"
            type="number"
            min="1"
            max="5"
            step="1"
            placeholder="1"
            required />
          <div class="error-message" id="error-number-person"></div>
        </div>
        <button
          class="btn"
          type="submit"
          aria-label="Rechercher le trajet sur ecoride">
          Rechercher
        </button>
      </div>
      <div id="feedback-form" role="alert"></div>
    </form>

  </section>

  <main id="main-page">

  </main>


  <footer id="footer-banner">

    <div class="infos">
      <div class="info">
        <h3>Suivez-nous</h3>
        <div class="icons">
          <a href="facebook"><img src="./assets/img/icons/Facebook.png" alt="Facebook de la plateforme de covoiturage EcoRide"></a>
          <a href="instagram"><img src="./assets/img/icons/Instagram.png" alt="Instagram de la plateforme de covoiturage EcoRide"></a>
        </div>
      </div>
      <div class="info">
        <h3>Autres information</h3>
        <ul>
          <li><a href="/faq">FAQ</a></li>
          <li><a href="/mentions-legales">Mentions Légales</a></li>
          <li><a href="/conditions-generales-ventes">CGV</a></li>
          <li><a href="/plan-du-site">Plan du site</a></li>
        </ul>
      </div>
      <div class="info">
        <h3><a href="/formulaire-contact">Contactez nous</a></h3>
      </div>

    </div>
    <div class="copyright">
      <p> &copy; 2025 Ecoride. Tous droits réservés. </p>
    </div>

  </footer>

</body>

</html>