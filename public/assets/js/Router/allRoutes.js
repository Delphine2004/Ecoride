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
    "/mentions-legales",
    "Mentions Legales",
    "./assets/view/legalNotices.html"
  ),
  new Route(
    "/rechercher",
    "Rechercher un trajet",
    "./assets/view/home.html",
    []
  ),
  new Route("/publier", "Publier un trajet", "./assets/view/addRide.html"),
  new Route("/connexion", "Connexion", "./assets/view/auth/login.html", []),
  new Route(
    "/inscription",
    "Inscription",
    "./assets/view/auth/registration.html",
    ["disconnected"]
  ),

  // Routes accessibles pour les clients et les utilisateurs
  new Route("/compte", "Mon compte", "./assets/view/auth/account.html", [
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
