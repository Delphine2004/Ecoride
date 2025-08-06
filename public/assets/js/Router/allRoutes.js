import Route from "./Route.js";

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
  new Route("/signin", "Connexion", "./assets/view/auth/signin.html", []),
  new Route("/signup", "Inscription", "./assets/view/auth/signup.html", []),

  // Routes accessibles pour les clients et les utilisateurs
  new Route("public/account", "Mon compte", "./assets/view/auth/account.html", [
    "user",
    "admin",
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
