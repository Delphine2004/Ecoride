import { Api } from "../Model/Api.js";

export async function fetchDriverById(driverId) {
  if (!driverId) return null;

  const api = new Api("./api.php");

  const response = await api.get(`/users/${driverId}`);
  if (!response.ok) {
    console.error("Impossible de récupérer les infos conducteur");
    return null;
  }

  const driverData = await response.json();
  return driverData;
}
