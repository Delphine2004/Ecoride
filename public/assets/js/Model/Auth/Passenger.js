export class Passenger extends User {
  constructor(data) {
    super(data); // appel du constructeur parent
    this.login = data.login;
    this.phone = data.phone;
    this.address = data.address;
    this.city = data.city;
    this.zipCode = data.zipCode;
    this.uriPicture = data.uriPicture;
    this.credits = Number(data.credits);
  }

  // Les getters
  // A FAIRE
  getPassengerInfo() {
    return `<div class="passenger-info"></div>`;
  }

  getPicture() {
    return `<div class="user-picture"> <img src="${this.uriPicture}" alt="photo de ${this.login}"></div>`;
  }

  getPassengerToJSON() {
    return {
      id: this.userId,
      lastName: this.lastName,
      firstName: this.firstName,
      email: this.email,
      role: this.role,
      login: this.login,
      phone: this.phone,
      address: this.address,
      city: this.city,
      zipCode: this.zipCode,
      uriPicture: this.uriPicture,
      credits: this.credits,
    };
  }

  // Les setters
  setPassengerInfo(updates) {
    if (updates.login !== undefined) {
      this.login = updates.login;
    }
    if (updates.phone !== undefined) {
      this.phone = updates.phone;
    }
    if (updates.address !== undefined) {
      this.address = updates.address;
    }
    if (updates.city !== undefined) {
      this.city = updates.city;
    }
    if (updates.zipCode !== undefined) {
      this.zipCode = updates.zipCode;
    }
    if (updates.uriPicture !== undefined) {
      this.uriPicture = updates.uriPicture;
    }
    if (updates.credits !== undefined) {
      this.credits = updates.credits;
    }
  }
}
