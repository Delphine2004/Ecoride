import { Driver } from "./Auth/Driver.js";
import { fetchDriverById } from "../Service/UserService.js";

export class Ride {
  constructor(data) {
    this.rideId = data.rideId;
    this.driverId = data.driverId ?? null;
    this.driver = data.driver ? new Driver(data.driver) : null;

    this.departureDateTime = data.departureDateTime?.date
      ? new Date(data.departureDateTime.date)
      : null;
    this.arrivalDateTime = data.arrivalDateTime?.date
      ? new Date(data.arrivalDateTime.date)
      : null;

    this.departurePlace = data.departurePlace;
    this.arrivalPlace = data.arrivalPlace;
    this.rideStatus = data.rideStatus;

    this.price = Number(data.price);
    this.availableSeats = Number(data.availableSeats);

    this.duration =
      data.duration ??
      (this.departureDateTime && this.arrivalDateTime
        ? Math.round((this.arrivalDateTime - this.departureDateTime) / 60000) // minutes
        : null);
  }

  // Formate la date
  formatDate(date) {
    if (!date) return "Date non précisée";
    return date.toLocaleDateString("fr-FR", {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Formate la durée
  formatDuration(minutes) {
    if (!minutes) return "Durée inconnue";
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h}h${m.toString().padStart(2, "0")}`;
  }

  // Vérifie si le trajet est complet
  isFull() {
    return this.availableSeats <= 0 || this.rideStatus === "complet";
  }

  // Permet d'ajouter l'objet User
  async attachDriver() {
    this.driver = await fetchDriverById(this.driverId);
  }

  // Les getters
  getRideInfo() {
    return `    <div class="ride-info">
      <div class="departure ride-item">
      <h4>Départ</h4>
        <div class="ride-item">${this.departurePlace}</div> 
        <div class="ride-item"> ${this.formatDate(this.departureDateTime)}</div>
      </div>
      <div class="arrival ride-item">
      <h4>Arrivée</h4>
        <div class="ride-item">${this.arrivalPlace}</div> 
        <div class="ride-item"> ${this.formatDate(this.arrivalDateTime)}</div>
      </div>
      <div class="big-screen">----------</div>
      <div class="ride-item">
        <div class="ride-item" >Durée : ${this.formatDuration(
          this.duration
        )}</div>
        <div class="ride-item">Disponibilité : ${this.availableSeats}</div>
      </div>

      <div class="big-screen">----------</div>

      <div class="driver-info"> ${
        this.driver
          ? this.driver.getDriverInfo()
          : `Conducteur #${this.driverId ?? "non précisé"}`
      }</div>

      <div class="ride-item">
      <div class="price">${this.price} crédits</div>
      <button class="join-ride" data-ride-id="${
        this.rideId
      }">Participer</button>
    </div>
     </div>`;
  }

  getRideToJSON() {
    return {
      rideId: this.rideId,
      departureDateTime: this.departureDateTime,
      departurePlace: this.departurePlace,
      arrivalDateTime: this.arrivalDateTime,
      arrivalPlace: this.arrivalPlace,
      price: this.price,
      availableSeats: this.availableSeats,
      rideStatus: this.rideStatus,
    };
  }
}
