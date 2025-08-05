export class User {
  constructor(id, login, email, password, role) {
    this.id = id;
    this.login = login;
    this.email = email;
    this._password = password;
    this.role = role;
  }

  getUserInfo() {
    return `Login : ${this.login} - Email : ${this.email}`;
  }

  getRole() {
    return "user";
  }
}
