import { allRoutes, route404, websiteName } from "./allRoutes.js";

// Fonction pour trouver une route à partir d'une URL
export function getRouteByUrl(url) {
  return allRoutes.find((route) => route.url === url) || route404;
}

export async function loadContent() {
  const path = window.location.pathname;
  const route = getRouteByUrl(path);
  //console.log("Route récupérée :", route);
  //console.log("loadPage existe ?", typeof route.loadPage);

  if (!route.isAuthorized()) {
    window.location.replace("/");
    return;
  }

  await route.loadPage(websiteName);
}

// Gère les clics sur les liens internes
export async function routeEvent(e) {
  e.currentTarget.preventDefault();
  const url = e.target.href;
  window.history.pushState({}, "", url);
  await loadContent();
}
