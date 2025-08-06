import { FormManager } from "../../utils/FormManager";
import { Api } from "../../Model/Api";

function registration() {
  console.log("JS chargé !"); // test de chargement

  const registerForm = document.getElementById("registration-form");
  if (!registerForm) return;

  const results = document.getElementById("feedback-form");

  // Création du gestionnaire de formulaire qui gére les validation
  const formManager = new FormManager();

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
    //console.log("JS a bien intercepté l'envoi !"); // test

    // Valider toutes les données avec la fonction validateForm()
    const isValid = formManager.validateForm(inputs);
    if (!isValid) return;

    // Puis les stocker dans un objet
    const cleanInputs = formManager.getCleanInputs(inputs);

    // Instanciation de la class Api
    const api = new Api("/ECF/public/api.php");

    // TODO à rajouter sur les autres formulaires
    const submitBtn = registerForm.querySelector("button[type=submit]");
    submitBtn.disabled = true;

    try {
      // appel de la méthode post de la classe api
      const userData = await api.post("/inscription", cleanInputs);
      //console.log("Réponse de l’API :", userData); // test

      if (userData.success) {
        // TODO - Redirection ou autre logique après connexion réussie
        results.innerHTML = `<p class="success">Inscription réussie. Redirection en cours...</p>`;
        setTimeout(() => {
          window.location.href = "/ECF/public/index.php?pages=home"; // à modifier
        }, 1500);
      } else {
        results.innerHTML = `<p class="error">${
          userData.message || "Identifiants incorrects."
        }</p>`;
      }
    } catch (error) {
      // TODO - ne pas afficher les erreurs mais plutot un message
      results.innerHTML = `<p class="error">Erreur : ${error.message}</p>`;
    } finally {
      // TODO - rajouter finally sur les autres formulaires
      submitBtn.disabled = false;
    }
  });
}
