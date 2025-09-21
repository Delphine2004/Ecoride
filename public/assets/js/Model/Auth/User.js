export class User {
  #password;
  constructor(data) {
    this.userId = data.userId;
    this.lastName = data.lastName;
    this.firstName = data.firstName;
    this.email = data.email;
    this.#password = data.password;
    this.role = data.role;
  }

  // Vérifications
  checkPassword(password) {
    return this.#password === password;
  }

  isPassenger() {
    return this.role === "passager";
  }

  isDriver() {
    return this.role === "conducteur";
  }

  isEmployee() {
    return this.role === "employé";
  }

  isAdmin() {
    return this.role === "admin";
  }

  // Les getters
  getRole() {
    return this.role;
  }

  getUserToJSON() {
    return {
      id: this.userId,
      lastName: this.lastName,
      firstName: this.firstName,
      email: this.email,
      role: this.role,
    };
  }

  // Les setters
  setUserInfo(updates) {
    if (updates.email !== undefined) {
      this.email = updates.email;
    }
    if (updates.role !== undefined) {
      this.role = updates.role;
    }
    if (updates.password !== undefined) {
      this.#password = updates.password;
    }
  }
}
