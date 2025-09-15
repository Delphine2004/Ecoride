import { FormManager } from "../Utils/FormManager.js";
import { Api } from "../Model/Api.js";

export function searchRide() {
  //console.log("JS search chargé !");

  const searchForm = document.getElementById("search-form");
  //console.log("Formulaire trouvé :", searchForm);
  if (!searchForm) return; // vérification que le formulaire existe

  const results = document.getElementById("feedback-form");

  // Création du gestionnaire de formulaire qui gère les validations
  const formManager = new FormManager(searchForm);

  // Stockage des éléments dans un objet
  const inputs = {
    ville_Depart: document.getElementById("departure-place"),
    ville_arrivee: document.getElementById("arrival-place"),
    date_depart: document.getElementById("departure-date"),
    nombre_personne: document.getElementById("number-person"),
  };

  // Boucle de validation en temps réel sur les éléments du formulaire -(Il faut que les champs aient un attribut type)
  Object.values(inputs).forEach((input) => {
    input.addEventListener("input", (event) => {
      const { value, id, dataset, type } = event.target;
      const fieldType = dataset.type || type || "text";
      //console.log(`[INPUT] ${id} = "${value}", type détecté: ${fieldType}`);
      formManager.validateInputs(value, id, fieldType);
    });
  });

  // -------------------------Envoi du formulaire ------------------

  searchForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.log("Soumission de recherche interceptée"); // test

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
      const trajets = await api.get("/rechercher", cleanInputs);
      console.log("Réponse de l’API :", trajets);

      //---- Partie à modifier ---- /
      results.innerHTML = trajets.length
        ? trajets
            .map((t) => `<div>${t.ville_depart} → ${t.ville_arrivee}</div>`)
            .join("")
        : "<p>Aucun résultat trouvé.</p>";
    } catch (error) {
      console.error("Erreur lors de l’appel à l’API :", error);
      // TODO - ne pas afficher les erreurs mais plutot un message
      results.innerHTML = `<p class="error">Erreur : ${error.message}</p>`;
    } // rajouter finally
  });
}
