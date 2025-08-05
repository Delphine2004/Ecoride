export class Ride {
  constructor(
    departureDate,
    departureTime,
    departurePlace,
    arrivalDate,
    arrivalTime,
    arrivalPlace,
    price,
    availableSeats,
    distanceKm,
    duration
  ) {
    this.departureDate = departureDate;
    this.departureTime = departureTime;
    this.departurePlace = departurePlace;
    this.arrivalDate = arrivalDate;
    this.arrivalTime = arrivalTime;
    this.arrivalPlace = arrivalPlace;
    this.price = parseFloat(price); // prix à virgule possible
    this.availableSeats = parseInt(availableSeats, 10); // nombre maximum de 10 chiffre
    this.distanceKm = parseFloat(distanceKm); // km en nombre à virgule
    this.duration = duration;
  }

  getRideInfo() {
    return `Ville de départ : ${this.departurePlace} - Ville d'arrivée : ${this.arrivalPlace} - Date de départ : ${this.departureDate} - Heure de départ : ${this.departureTime}`;
  }

  getRideToJSON() {
    return {
      departureDate: this.departureDate,
      departureTime: this.departureTime,
      departurePlace: this.departurePlace,
      arrivalDate: this.arrivalDate,
      arrivalTime: this.arrivalTime,
      arrivalPlace: this.arrivalPlace,
      price: this.price,
      availableSeats: this.availableSeats,
      distanceKm: this.distanceKm,
      duration: this.duration,
    };
  }
}
