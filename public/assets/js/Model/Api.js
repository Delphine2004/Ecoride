import { getToken } from "../Utils/auth.js";

export class Api {
  constructor(baseUrl = "") {
    this.baseUrl = baseUrl;
  }

  async request(endpoint, method = "GET", data = null, token = null) {
    const headers = {
      "Content-Type": "application/json",
    };

    if (!token) {
      token = getToken();
    }

    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const options = {
      method,
      headers,
    };

    if (data && method !== "GET") {
      options.body = JSON.stringify(data);
    }

    // Construire l'URL avec query string pour GET
    let url = endpoint;
    if (method === "GET" && data && Object.keys(data).length > 0) {
      const query = new URLSearchParams(data).toString();
      url += (endpoint.includes("?") ? "&" : "?") + query;
    }

    try {
      const response = await fetch(`${this.baseUrl}${url}`, options);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Erreur ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error(`[API ${method}] ${endpoint} :`, error.message);
      throw error;
    }
  }

  get(endpoint, params = {}, token = null) {
    return this.request(endpoint, "GET", params, token);
  }

  post(endpoint, data = {}, token = null) {
    return this.request(endpoint, "POST", data, token);
  }

  put(endpoint, data = {}, token = null) {
    return this.request(endpoint, "PUT", data, token);
  }

  delete(endpoint, data = {}, token = null) {
    return this.request(endpoint, "DELETE", data, token);
  }
}
