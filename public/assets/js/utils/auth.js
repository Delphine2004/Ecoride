// Générer un token
export function setToken(token) {
  localStorage.setItem("authToken", token);
}

// Récupèrer le token depuis localStorage
export function getToken() {
  return localStorage.getItem("authToken");
}

// Supprimer le token à la déconnexion
export function clearToken() {
  localStorage.removeItem("authToken");
}

// Stoker un cookie
export function setCookie(name, value, days) {
  let expires = "";
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie =
    name + "=" + (value || "") + expires + "; path=/; SameSite=Strict";
}
// récuperer un cookie par nom
export function getCookie(name) {
  const nameEQ = name + "=";
  const ca = document.cookie.split(";");
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === " ") c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

// supprimer un cookie
export function eraseCookie(name) {
  document.cookie = name + "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
}
