import { searchRide } from "./Controller/searchController.js";
import { addRide } from "./Controller/addRideController.js";
import { login } from "./Controller/Auth/loginController.js";
import { registration } from "./Controller/Auth/registrationController.js";
import { logout } from "./Controller/Auth/logout.js";
import { routeEvent, loadContent } from "./Router/Router.js";

import { showAndHideElementsForRole } from "./Utils/role.js";

// console.log("main chargé"); // test de chargement

// Gestion de l'événement de retour en arrière dans l'historique du navigateur
//---MODIFIER AVEC UN ADDEVENTLISTENER
window.onpopstate = loadContent;

// Assignation de la fonction routeEvent à la propriété route de la fenêtre
window.route = routeEvent;

// Chargement du contenu de la page au chargement initial

document.addEventListener("DOMContentLoaded", async () => {
  try {
    await loadContent(); // attendre que le contenu soit injecté sur la page
    showAndHideElementsForRole();
    //------- Récupération des éléments aprés le chargement de la page

    const path = window.location.pathname;

    // Formulaires
    const searchForm = document.getElementById("search-form");
    const addForm = document.getElementById("addRide-form");
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("registration-form");
    //  const contactForm = document.getElementById("contact-form"); // pas encore fait

    // Bouton de deconnexion
    const logoutBtn = document.getElementById("logout-btn");

    /* ---------------------------------------------------- */

    // Initialisation des formulaires accessibles sans connexion
    if (path.includes("/") && searchForm) {
      searchRide();
    }
    if (path.includes("connexion") && loginForm) {
      login();
    }
    if (path.includes("inscription") && registerForm) {
      registration();
    }

    if (path.includes("publier") && addForm) {
      addRide();
    }

    if (logoutBtn) {
      logoutBtn.addEventListener("click", (e) => {
        e.preventDefault();
        logout();
      });
    }
  } catch (error) {
    console.error(error);
  }
});
