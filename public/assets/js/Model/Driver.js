import { User } from "./User";

export class Driver extends User {
  constructor(pseudo, picture, rating, preference) {
    this.pseudo = pseudo;
    this.picture = picture;
    this.rating = rating;
    this.preference = preference;
  }

  getDriverInfo() {
    return `Pseudo : ${this.pseudo} - Note : ${this.rating} - Préférences : ${this.preference} `;
  }

  getPicture() {
    return `<img src="${this.picture}" alt="photo de ${this.pseudo}">`;
  }

  getDriverToJSON() {
    return {
      pseudo: this.pseudo,
      picture: this.picture,
      rating: this.rating,
      preference: this.preference,
    };
  }
}
