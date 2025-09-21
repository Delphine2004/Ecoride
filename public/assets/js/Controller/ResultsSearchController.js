import { Ride } from "../Model/Ride.js";

export function showRidesResult() {
  console.log("JS Result chargé !");

  const results = document.getElementById("results");
  const ridesData = JSON.parse(sessionStorage.getItem("rides")) || [];

  // Instance de ride
  const rides = ridesData.map((data) => new Ride(data));

  if (rides.length === 0) {
    results.innerHTML =
      "<p>Aucun résultat trouvé. Merci de faire une nouvelle recherche pour une date plus proche.</p>";
  } else {
    results.innerHTML = rides.map((ride) => ride.getRideInfo()).join("");
  }

  sessionStorage.removeItem("rides");
}
