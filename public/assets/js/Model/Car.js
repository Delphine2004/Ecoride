export class Car {
  constructor(brand, carModel, color, power, numberOfSeats) {
    this.brand = brand;
    this.carModel = carModel;
    this.color = color;
    this.power = power;
    this.numberOfSeats = parseInt(numberOfSeats); // parseInt pour garantir le type numérique
  }

  getCarInfo() {
    return `Modèle : ${this.carModel} - Marque : ${this.brand} - Couleur : ${this.color} - Energie : ${this.power}`;
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

  setCarInfo() {}
}
