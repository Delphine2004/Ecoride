import { FormManager } from "../../Utils/FormManager.js";
import { setToken, setCookie, clearToken } from "../../Utils/auth.js";
import { showAndHideElementsForRole } from "../../Utils/role.js";
import { Api } from "../../Model/Api.js";

export function registration() {
  //console.log("JS Registration chargé !"); // test de chargement

  const registerForm = document.getElementById("registration-form");
  //console.log("Formulaire trouvé :", registerForm);
  if (!registerForm) return;

  const results = document.getElementById("feedback-form");

  // Création du gestionnaire de formulaire qui gére les validation
  const formManager = new FormManager(registerForm);

  // Stockage des éléments dans un objet
  const inputs = {
    prenom: document.getElementById("first-name"),
    nom: document.getElementById("last-name"),
    email: document.getElementById("email"),
    password: document.getElementById("password"),
  };

  // Boucle de validation en temps réel sur les éléments du formulaire -(Il faut que les champs aient un attribut type)
  Object.values(inputs).forEach((input) => {
    input.addEventListener("input", (event) => {
      const { value, id, dataset, type } = event.target;
      const fieldType = dataset.type || type || "text";
      formManager.validateInputs(value, id, fieldType);
    });
  });

  // ---------------------- Envoi du formulaire-----------------------

  registerForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.log("Soumission d'inscription interceptée"); // test

    // Valider toutes les données avec la fonction validateForm()
    const isValid = formManager.validateForm(inputs);
    if (!isValid) return;

    // Puis les stocker dans un objet
    const cleanInputs = formManager.getCleanInputs(inputs);
    //console.log("Données nettoyées à envoyer :", cleanInputs);

    // Instanciation de la class Api
    const api = new Api("./api.php");

    try {
      // appel de la méthode post de la classe api
      const userData = await api.post("/inscription", cleanInputs);
      console.log("Réponse de l’API :", userData); // test

      //---- Partie à modifier ---- /
      if (userData.success) {
        // TODO - Redirection ou autre logique après connexion réussie
        results.innerHTML = `<p class="success">Inscription réussie. Redirection en cours...</p>`;
        setTimeout(() => {
          window.location.href = "/connexion";
        }, 1500);
      } else {
        results.innerHTML = `<p class="error">${
          userData.message || "Identifiants incorrects."
        }</p>`;
      }
    } catch (error) {
      console.error("Erreur lors de l’appel à l’API :", error);
      // TODO - ne pas afficher les erreurs mais plutot un message
      results.innerHTML = `<p class="error">Erreur : ${error.message}</p>`;
    } // rajouter finally
  });
}

registration();
