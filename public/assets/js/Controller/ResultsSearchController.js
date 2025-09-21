import { Ride } from "../Model/Ride.js";

export function showRidesResult() {
  //console.log("JS Result chargé !");

  // Récupération des éléments
  const results = document.getElementById("results");
  const ridesCount = document.getElementById("rides-count");
  const filterInputs = document.querySelectorAll(".input-filter");

  // Vérifie si des résultats existent en session
  const ridesDataRaw = sessionStorage.getItem("rides");
  if (!ridesDataRaw) {
    results.innerHTML = "<p>Veuillez effectuer une recherche.</p>";
    ridesCount.textContent = "";
    return;
  }

  // Récupération des données
  const ridesData = JSON.parse(sessionStorage.getItem("rides")) || [];
  const rides = ridesData.map((data) => new Ride(data));

  // Fonction qui permet d'afficher les résultats
  function renderRide(ridesToDisplay) {
    const filteredRides = ridesToDisplay.filter((ride) => !ride.isFull());
    if (filteredRides.length === 0) {
      results.innerHTML =
        "<p>Aucun résultat trouvé. Merci de faire une nouvelle recherche pour une date plus proche.</p>";
    } else {
      // affichage du nombre de trajet trouvé
      ridesCount.textContent = `Nombre de trajets trouvés : ${filteredRides.length}`;
      results.innerHTML = filteredRides
        .map((ride) => ride.getRideInfo())
        .join("");
    }
  }

  // Affichage sans filtre
  renderRide(rides);

  // Filtrer en temps réel
  filterInputs.forEach((input) => {
    input.addEventListener("input", applyFilters);
    input.addEventListener("change", applyFilters);
  });

  // Fonction qui permet d'appliquer les filtres
  function applyFilters() {
    let filteredRides = rides.filter((ride) => !ride.isFull());

    const maxPrice = document.getElementById("filter-price").value;
    const maxDuration = document.getElementById("filter-duration").value;
    const ecoOnly = document.getElementById("filter-power").checked;
    const superDriver = document.getElementById("filter-driver-rate").checked;

    if (maxPrice) {
      filteredRides = filteredRides.filter((ride) => ride.price <= maxPrice);
    }

    if (maxDuration) {
      filteredRides = filteredRides.filter(
        (ride) => ride.duration <= maxDuration
      );
    }

    if (ecoOnly) {
      filteredRides = filteredRides.filter((ride) => ride.isEco);
    }

    if (superDriver) {
      filteredRides = filteredRides.filter(
        (ride) => ride.driver?.rating >= 4.5
      );
    }
    renderRide(filteredRides);
    ridesCount.textContent = `Nombre de trajets trouvés : ${filteredRides.length}`;

    sessionStorage.removeItem("rides");
  }
}
