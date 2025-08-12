import { getToken, getCookie } from "./auth.js";

const RoleCookieName = "role";
export function getRole() {
  return getCookie(RoleCookieName);
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

const apiUrl = "https://127.0.0.1:8000/api/";
export function getInfosUser() {
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
