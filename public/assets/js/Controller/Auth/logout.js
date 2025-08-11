import { clearToken, eraseCookie } from "./auth";

export function logout() {
  clearToken();
  eraseCookie("userRole");
  window.location.href = "/login";
}
