import { searchRide } from "./Controller/searchController";
import { connexion } from "./Controller/loginController";
import { registration } from "./Controller/registerController";

document.addEventListener("DOMContentLoaded", () => {
  try {
    // const role = document.body.dataset.role;
    const searchForm = document.getElementById("search-form");
    const addForm = document.getElementById("add-form");
    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("registration-form");
    const contactForm = document.getElementById("contact-form");

    // Initialisation des formulaires accessibles sans connexion

    if (searchForm) {
      searchRide();
    }

    if (contactForm) {
      contact();
    }
    if (loginForm) {
      connexion();
    }
    if (registerForm) {
      registration();
    }

    // ------------------- il manque le formulaire d'ajout ---------------------
  } catch (error) {
    alert("Erreur d'initialisation.");
  }
});
