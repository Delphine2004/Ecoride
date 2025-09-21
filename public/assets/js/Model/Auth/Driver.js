export class Driver extends Passenger {
  constructor(data) {
    super(data); // appel du constructeur parent
    this.rating = data.rating;
    this.licenceNo = data.licenceNo;
    this.preferences = data.preferences;
  }

  // Les getters
  getDriverInfo() {
    return `<div class="driver-info"> ${this.login} - Note : ${this.rating} - Préférences : ${this.preferences} </div>`;
  }

  getDriverToJSON() {
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
      rating: this.rating,
      licenceNo: this.licenceNo,
      preferences: this.preferences,
    };
  }

  // Les setters
  setDriverInfo(updates) {
    if (updates.rating !== undefined) {
      this.rating = updates.rating;
    }
    if (updates.licenceNo !== undefined) {
      this.licenceNo = updates.licenceNo;
    }
    if (updates.preferences !== undefined) {
      this.preferences = updates.preferences;
    }
  }
}
