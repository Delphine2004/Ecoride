import { FormManager } from "../Utils/FormManager.js";
import { Api } from "../Model/Api.js";

export function addRide() {
  //console.log("JS add chargé !");

  const addForm = document.getElementById("addRide-form");
  //console.log("Formulaire trouvé :", addForm);
  if (!addForm) return; // Vérification si le fomulaire existe

  const results = document.getElementById("feedback-form");

  // Création du gestionnaire de formulaire qui gère les validations
  const formManager = new FormManager(addForm);

  // Stockage des éléments dans un objet
  const inputs = {
    ville_Depart: document.getElementById("departure-place"),
    ville_arrivee: document.getElementById("arrival-place"),
    date_depart: document.getElementById("departure-date"),
    heure_depart: document.getElementById("departure-time"),
    siege_dispo: document.getElementById("available_seats"),
    prix: document.getElementById("price"),
    vehicule: document.getElementById("car"), // voir si modifié dans addform
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

  addForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    console.log("Soumission d'ajout de trajet interceptée"); // test

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
      const trajets = await api.post("/publier", cleanInputs);
      console.log("Réponse de l’API :", trajets);

      results.textContent = "Votre trajet a bien été ajouté.";
    } catch (error) {
      console.error("Erreur lors de l’appel à l’API :", error);
      results.textContent = `Erreur : ${error.message}`;
    } // rajouter finally
  });
}
