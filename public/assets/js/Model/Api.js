export class Api {
  constructor(baseUrl = "") {
    this.baseUrl = baseUrl;
  }

  // fonction request utiliser dans la classe Api
  async request(endpoint, method = "GET", data = null, token = null) {
    const headers = {
      "Content-Type": "application/json",
    };

    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const options = {
      method,
      headers,
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, options);

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

  get(endpoint, token = null) {
    return this.request(endpoint, "GET", null, token);
  }

  post(endpoint, data, token = null) {
    return this.request(endpoint, "POST", data, token);
  }

  put(endpoint, data, token = null) {
    return this.request(endpoint, "PUT", data, token);
  }

  delete(endpoint, token = null) {
    return this.request(endpoint, "DELETE", null, token);
  }
}
