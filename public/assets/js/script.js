const RoleCookieName = "role";
export function getRole() {
  return getCookie(RoleCookieName);
}

// Fonction pour récupérer la route correspondant à une URL donnée
export function getRouteByUrl(url) {
  return allRoutes.find((route) => route.url === url) || route404;
} // route404 est un fallback

const tokenCookieName = "accesstoken";
function signout() {
  eraseCookie(tokenCookieName);
  eraseCookie(RoleCookieName);
  window.location.reload();
}
const signoutBtn = document.getElementById("signout-btn");
signoutBtn.addEventListener("click", signout);

function setToken(token) {
  setCookie(tokenCookieName, token, 7);
}

function getToken() {
  return getCookie(tokenCookieName);
}

function setCookie(name, value, days) {
  let expires = "";
  if (days) {
    let date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
  let nameEQ = name + "=";
  let ca = document.cookie.split(";");
  for (const element of ca) {
    let c = element;
    while (c.startsWith(" ")) c = c.substring(1, c.length);
    if (c.startsWith(nameEQ)) return c.substring(nameEQ.length, c.length);
  }
  return null;
}

function eraseCookie(name) {
  document.cookie = name + "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
}

export function isConnected() {
  return !(getToken() == null || getToken() == undefined);
}

export function showAndHideElementsForRole() {
  const userConnected = isConnected();
  const role = getRole();

  let AllElementstoEdit = document.querySelectorAll("[data-show]");

  AllElementstoEdit.forEach((element) => {
    switch (element.dataset.show) {
      case "disconnected":
        if (userConnected) {
          element.classList.add("d-none"); // classe bootstrap
        }
        break;
      case "connected":
        if (!userConnected) {
          element.classList.add("d-none"); // classe bootstrap
        }
        break;
      case "admin":
        if (!userConnected || role != "admin") {
          element.classList.add("d-none"); // classe bootstrap
        }
        break;
      case "client":
        if (!userConnected || role != "client") {
          element.classList.add("d-none"); // classe bootstrap
        }
        break;
    }
  });
}

function sanitizeHtml(text) {
  // Créez un élément HTML temporaire de type "div"
  const tempHtml = document.createElement("div");

  // Affectez le texte reçu en tant que contenu texte de l'élément "tempHtml"
  tempHtml.textContent = text;

  // Utilisez .innerHTML pour récupérer le contenu de "tempHtml"
  // Cela va "neutraliser" ou "échapper" tout code HTML potentiellement malveillant
  return tempHtml.innerHTML;
}

const apiUrl = "https://127.0.0.1:8000/api/";
function getInfosUser() {
  let myHeaders = new Headers();
  myHeaders.append("X-AUTH-TOKEN", getToken());

  let requestOptions = {
    method: "GET",
    headers: myHeaders,
    redirect: "follow",
  };

  fetch(apiUrl + "account/me", requestOptions)
    .then((response) => {
      if (response.ok) {
        return response.json();
      } else {
        console.log("Impossible de récupérer les informations utilisateur");
      }
    })
    .then((result) => {
      return result;
    })
    .catch((error) => {
      console.error(
        "erreur lors de la récupération des données utilisateur",
        error
      );
    });
}
