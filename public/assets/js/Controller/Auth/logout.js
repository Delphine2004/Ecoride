import { clearToken, eraseCookie } from "../../Utils/auth.js";
import { showAndHideElementsForRole } from "../../Utils/role.js";

export function logout() {
  clearToken();
  eraseCookie("userRole");
  showAndHideElementsForRole();
  window.location.href = "/login";
}
