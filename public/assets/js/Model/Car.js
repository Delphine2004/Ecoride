export class Car {
  constructor(data) {
    this.carId = data.carId;
    this.brand = data.brand;
    this.carModel = data.carModel;
    this.color = data.color;
    this.power = data.power;
    this.numberOfSeats = Number(data.numberOfSeats);
  }

  // Vérifications
  isEco() {
    return this.power === "Electrique";
  }

  // Les getters
  getCarInfo() {
    return `<div class="car-info">"Modèle : ${this.carModel} - Marque : ${this.brand} - Couleur : ${this.color} - Energie : ${this.power}</div>`;
  }

  getCarToJSON() {
    return {
      brand: this.brand,
      carModel: this.carModel,
      color: this.color,
      power: this.power,
      numberOfSeats: this.numberOfSeats,
    };
  }
}
