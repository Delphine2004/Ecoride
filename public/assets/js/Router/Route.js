export default class Route {
  constructor(url, title, pathHtml, authorize = [], pathJS = "") {
    this.url = url;
    this.title = title;
    this.pathHtml = pathHtml;
    this.authorize = authorize; // gére les rôles
    this.pathJS = pathJS;
  }
}

/*
[] -> Tout le monde peut y accéder
["disconnected"] -> Réserver aux utilisateurs déconnecté 
["user"] -> Réserver aux utilisateurs avec le rôle client 
["admin"] -> Réserver aux utilisateurs avec le rôle admin 
["admin", "user"] -> Réserver aux utilisateurs avec le rôle client OU admin
*/
