import Route from "./Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";

// alert("coucou"); // teste de chargement

const mainPage = document.getElementById("main-page");

// Création d'une route pour la page 404 (page introuvable)
const route404 = new Route(
  "/404",
  "Page introuvable",
  "./assets/view/404.html",
  []
);

// Fonction pour récupérer la route correspondant à une URL donnée
function getRouteByUrl(url) {
  let currentRoute = null;
  // Parcours de toutes les routes pour trouver la correspondance
  allRoutes.forEach((element) => {
    if (element.url === url) {
      currentRoute = element;
    }
  });
  // Si aucune correspondance n'est trouvée, on retourne la route 404
  if (currentRoute != null) {
    return currentRoute;
  } else {
    return route404;
  }
}

// Fonction pour charger le contenu de la page
async function LoadContentPage() {
  let path = window.location.pathname;

  // Récupération de l'URL actuelle
  const actualRoute = getRouteByUrl(path);

  // Vérifier les droits d'accés à la page
  const allRolesArray = actualRoute.authorize;

  if (allRolesArray.length > 0) {
    if (allRolesArray.includes("disconnected")) {
      if (isConnected()) {
        window.location.replace("/");
      }
    } else {
      const roleUser = getRole();
      if (!allRolesArray.includes(roleUser)) {
        window.location.replace("/");
      }
    }
  }

  // Récupération du contenu HTML de la route
  const html = await fetch(actualRoute.pathHtml).then((data) => data.text());
  // Ajout du contenu HTML à l'élément avec l'ID "main-page"
  mainPage.innerHTML = html;

  // Ajout du contenu JavaScript
  if (actualRoute.pathJS != "") {
    // Création d'une balise script
    let scriptTag = document.createElement("script");
    scriptTag.setAttribute("type", "text/javascript");
    scriptTag.setAttribute("src", actualRoute.pathJS);

    // Ajout de la balise script au corps du document
    document.querySelector("body").appendChild(scriptTag);
  }

  // Changement du titre de la page
  document.title = actualRoute.title + " - " + websiteName;

  // Afficher et masquer les élément en fonction du rôle
  showAndHideElementsForRole();
}

// Fonction pour gérer les événements de routage (clic sur les liens)
function routeEvent(e) {
  e = e || window.e;
  e.preventDefault();
  // Mise à jour de l'URL dans l'historique du navigateur
  window.history.pushState({}, "", e.target.href);
  // Chargement du contenu de la nouvelle page
  LoadContentPage();
}

// Gestion de l'événement de retour en arrière dans l'historique du navigateur
window.onpopstate = LoadContentPage;

// Assignation de la fonction routeEvent à la propriété route de la fenêtre
window.route = routeEvent;

// Chargement du contenu de la page au chargement initial
LoadContentPage();
