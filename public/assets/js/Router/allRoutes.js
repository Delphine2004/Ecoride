import Route from "./Route.js";

// Création d'une route pour la page 404 (page introuvable)
export const route404 = new Route(
  "/404",
  "Page introuvable",
  "./assets/view/404.html",
  []
);

export const allRoutes = [
  // Routes accessibles à tous
  new Route("/", "Accueil", "./assets/view/home.html", []),

  new Route(
    "/rechercher",
    "Rechercher un trajet",
    "./assets/view/rechercher.html",
    []
  ),
  // Rajouter disconnected
  new Route("/login", "Connexion", "./assets/view/auth/login.html", []),
  new Route(
    "/registration",
    "Inscription",
    "./assets/view/auth/registration.html",
    ["disconnected"]
  ),

  // Routes accessibles pour les clients et les utilisateurs
  new Route("/account", "Mon compte", "./assets/view/auth/account.html", [
    "connected",
  ]),
  new Route(
    "/editPassword",
    "Changement de mot de passe",
    "./assets/view/auth/editPassword.html",
    ["user", "admin"]
  ),
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "EcoRide";
