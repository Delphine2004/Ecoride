import { searchRide } from "./Controller/searchController.js";
import { login } from "./Controller/Auth/loginController.js";
import { registration } from "./Controller/Auth/registrationController.js";
import { routeEvent, loadContent } from "./router/Router.js";

//console.log("main chargé"); // test de chargement

// Gestion de l'événement de retour en arrière dans l'historique du navigateur
//---MODIFIER AVEC UN ADDEVENTLISTENER
window.onpopstate = loadContent;

// Assignation de la fonction routeEvent à la propriété route de la fenêtre
window.route = routeEvent;

// Chargement du contenu de la page au chargement initial

document.addEventListener("DOMContentLoaded", async () => {
  try {
    await loadContent(); // attendre que le contenu soit injecté sur la page

    const path = window.location.pathname;

    const searchForm = document.getElementById("search-form");
    // const addForm = document.getElementById("add-form"); // pas encore fait
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("registration-form");
    //  const contactForm = document.getElementById("contact-form"); // pas encore fait

    // Initialisation des formulaires accessibles sans connexion
    if (path.includes("") && searchForm) {
      searchRide();
    }
    if (path.includes("login") && loginForm) {
      login();
    }
    if (path.includes("registration") && registerForm) {
      registration();
    }
  } catch (error) {
    console.error(error);
  }
});
