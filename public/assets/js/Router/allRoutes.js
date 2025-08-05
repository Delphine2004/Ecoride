import Route from "./Route.js";

export const allRoutes = [
  // Routes accessibles à tous
  new Route("/", "Accueil", "/public/js/View/home.html", []),

  new Route(
    "/rechercher",
    "Rechercher un trajet",
    "/public/js/View/reserver.html",
    []
  ),

  new Route(
    "/signin",
    "Connexion",
    "/public/js/View/Auth/signin.html",
    ["disconnected"],
    "/js/Auth/signin.js"
  ),
  new Route(
    "/signup",
    "Inscription",
    "/public/js/View/Auth/signup.html",
    ["disconnected"],
    "/js/Auth/signup.js"
  ),

  // Routes accessibles pour les clients et les utilisateurs
  new Route("/account", "Mon compte", "/public/js/Auth/account.html", [
    "user",
    "admin",
  ]),
  new Route(
    "/editPassword",
    "Changement de mot de passe",
    "/public/js/View/auth/editPassword.html",
    ["user", "admin"]
  ),
  new Route(
    "/allResa",
    "Vos réservations",
    "/pages/reservations/allResa.html",
    ["user"]
  ),
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "EcoRide";
