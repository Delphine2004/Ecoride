import { FormManager } from "../Utils/FormManager.js";
import { Api } from "../Api.js";

export function searchRide() {
  //console.log("JS search chargé !");

  const formSearch = document.getElementById("search-form");
  console.log("Formulaire trouvé :", formSearch);
  if (!formSearch) return; // vérification que le formulaire existe

  const results = document.getElementById("feedback-form");
  //console.log("Zone de commentaire :", results);

  // Création du gestionnaire de formulaire qui gére les validation
  const formManager = new FormManager();

  // Stockage des éléments dans un objet
  const inputs = {
    ville_Depart: document.getElementById("departure-place"),
    ville_arrivee: document.getElementById("arrival-place"),
    date_depart: document.getElementById("departure-date"),
    nombre_personne: document.getElementById("number-person"),
  };

  /*
  // Récupération du jeton
  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");
    */

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

  formSearch.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.log("Soumission interceptée");

    // Valider toutes les données avec la fonction validateForm()
    const isValid = formManager.validateForm(inputs);
    console.log("Validation globale :", isValid);
    if (!isValid) return;

    // Puis les stocker dans un objet
    const cleanInputs = formManager.getCleanInputs(inputs);
    console.log("Données nettoyées à envoyer :", cleanInputs);

    // Instanciation de la class Api
    const api = new Api("/ECF/public/api.php");

    try {
      // appel de la méthode post de la classe api
      const trajets = await api.post("/recherche-covoiturage", cleanInputs);
      console.log("Réponse de l’API :", trajets);
      // -----------------------------------------Cette partie sera à modifier
      results.innerHTML = trajets.length
        ? trajets
            .map((t) => `<div>${t.ville_depart} → ${t.ville_arrivee}</div>`)
            .join("")
        : "<p>Aucun résultat trouvé.</p>";
    } catch (error) {
      console.error("Erreur lors de l’appel à l’API :", error);
      results.innerHTML = `<p class="error">Erreur : ${error.message}</p>`;
    }
  });
}
