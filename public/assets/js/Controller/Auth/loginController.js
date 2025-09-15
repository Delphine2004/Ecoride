import { FormManager } from "../../Utils/FormManager.js";
import { setToken, setCookie, clearToken } from "../../Utils/auth.js";
import { showAndHideElementsForRole } from "../../Utils/role.js";
import { Api } from "../../Model/Api.js";

export function login() {
  //console.log("JS Login chargé !"); // test de chargement

  const formLogin = document.getElementById("login-form");
  //console.log("Formulaire trouvé :", formLogin);
  if (!formLogin) return;

  const results = document.getElementById("feedback-form");

  // Création du gestionnaire de formulaire qui gère les validations
  const formManager = new FormManager(formLogin);

  // Stockage des éléments dans un objet
  const inputs = {
    email: document.getElementById("email"),
    password: document.getElementById("password"),
  };

  // Boucle de validation en temps réel sur les éléments du formulaire -(Il faut que les champs aient un attribut type) ----ESSAYER AVEC FORMMANAGER QUAND CE SERA CHARGE
  Object.values(inputs).forEach((input) => {
    input.addEventListener("input", (event) => {
      const { value, id, dataset, type } = event.target;
      const fieldType = dataset.type || type || "text";
      formManager.validateInputs(value, id, fieldType);
    });
  });

  // ---------------------- Envoi du formulaire-----------------------

  formLogin.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.log("Soumission de connexion interceptée"); // test

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
      const userData = await api.post("/connexion", cleanInputs);
      console.log("Réponse de l’API :", userData);

      //---- Partie à modifier ---- /
      if (userData.success) {
        if (userData.token) {
          setToken(userData.token);

          if (userData.role) {
            setCookie("userRole", userData.role, 7);
          }
        }

        showAndHideElementsForRole();

        results.innerHTML = `<p class="success">Connexion réussie, redirection...</p>`;
        // Redirection ou autre logique après connexion réussie
        setTimeout(() => {
          window.location.href = "/ECF/public/index.php?pages=home"; // à modifier
        }, 1500);
      } else {
        clearToken();
        results.innerHTML = `<p class="error">${
          userData.message || "Identifiants incorrects."
        }</p>`;
      }

      /* Il faut définir l'action */
    } catch (error) {
      console.error("Erreur lors de l’appel à l’API :", error);
      // TODO - ne pas afficher les erreurs mais plutot un message
      results.innerHTML = `<p class="error">Erreur : ${error.message}</p>`;
    } // rajouter finally
  });
}
