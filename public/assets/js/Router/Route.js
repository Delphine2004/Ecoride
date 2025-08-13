import {
  showAndHideElementsForRole,
  getRole,
  isConnected,
} from "../Utils/role.js";

export default class Route {
  constructor(url, title, pathHtml, authorize = []) {
    this.url = url;
    this.title = title;
    this.pathHtml = pathHtml;
    this.authorize = authorize; // gére les rôles
  }

  isAuthorized() {
    const roles = this.authorize;
    if (roles.length === 0) return true;
    if (roles.includes("disconnected")) return !isConnected();

    const roleUser = getRole();
    return roles.includes(roleUser);
  }

  // Fonction pour charger le contenu de la page
  async loadPage(websiteName) {
    const html = await fetch(this.pathHtml).then((data) => data.text());
    document.getElementById("main-page").innerHTML = html;
    document.title = `${this.title} - ${websiteName}`;
    showAndHideElementsForRole();
  }
}
/*
[] -> Tout le monde peut y accéder
["disconnected"] -> Réserver aux utilisateurs déconnecté 
["user"] -> Réserver aux utilisateurs avec le rôle client 
["admin"] -> Réserver aux utilisateurs avec le rôle admin 
["admin", "user"] -> Réserver aux utilisateurs avec le rôle client OU admin
*/
