<!DOCTYPE html>
<html lang="fr">

<head>
  <?php require_once LAYOUT_PATH . "head.php"; ?>
  <script src="/ECF/public/assets/js/main.js" type=module></script>
  <title>EcoRide</title>
</head>

<body>

  <header id="header-banner">

    <!-- Navbar grand écran -->
    <div class="bigNav nav">

      <div class="logo">
        <!-- Logo avec retour page accueil-->
        <a href="<?= PUBLIC_PATH ?>"><img src="<?= IMG_PATH ?>logo/logo.webp" alt="Logo de la plateforme de covoiturage EcoRide"></a>
      </div>

      <nav>
        <ul>
          <!-- Rechercher -->
          <li><a href="<?= PUBLIC_PATH ?>#hero">Rechercher un trajet</a></li>
          <!-- Publier -->
          <li><a href="<?= PUBLIC_PATH ?>publier">Publier un trajet</a></li>
          <!-- Se connecter -->
          <li><a href="<?= PUBLIC_PATH ?>connexion">Connexion</a></li>
        </ul>
      </nav>
    </div>



    <!-- NavBar petit écran -->
    <div class="smallNav nav">
      <!-- Le logo est à la deuxième place -->
      <nav>

        <div class="user-icon">
          <!-- Se connecter -->
          <a href="<?= PUBLIC_PATH ?>connexion"><img src="<?= IMG_PATH ?>icons/account_circle.png" alt="Espace utilisateur de la plateforme de covoiturage EcoRide"></a>
        </div>

        <div class="logo">
          <!-- Logo avec retour page accueil-->
          <a href="<?= PUBLIC_PATH ?>"><img src="<?= IMG_PATH ?>logo/logo.webp" alt="Logo de la plateforme de covoiturage EcoRide"></a>
        </div>

        <div class="icons">
          <!-- Rechercher -->
          <a href="<?= PUBLIC_PATH ?>#hero"><img src="<?= IMG_PATH ?>icons/search.png" alt="rechercher un trajet sur la plateforme de covoiturage EcoRide"></a>
          <!-- Publier -->
          <a href="<?= PUBLIC_PATH ?>publier"><img src="<?= IMG_PATH ?>icons/add_circle.png" alt="publier un trajet sur la plateforme de covoiturage EcoRide"></a>
        </div>

      </nav>
    </div>



  </header>