import { clearToken, eraseCookie } from "./auth.js";
import { showAndHideElementsForRole } from "./role.js";

export function logout() {
  clearToken();
  eraseCookie("userRole");
  showAndHideElementsForRole();
  window.location.href = "/login";
}
