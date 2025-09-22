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
    departure_place: document.getElementById("departure-place"),
    arrival_place: document.getElementById("arrival-place"),
    departure_date: document.getElementById("departure-date"),
    departure_time: document.getElementById("departure-time"),
    available_seats: document.getElementById("available-seats"),
    price: document.getElementById("price"),
    car: document.getElementById("car"), // voir si modifié dans addform
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

    // Fusion date + heure
    const departureDateTime = `${inputs.departure_date.value}T${inputs.departure_time.value}:00`;

    // Stocker les valeurs vérifiées
    const cleanInputs = {
      departure_place: inputs.departure_place.value,
      arrival_place: inputs.arrival_place.value,
      departure_date_time: departureDateTime,
      available_seats: inputs.available_seats.value,
      price: inputs.price.value,
      car: inputs.car.value,
    };
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
    }
  });
}
