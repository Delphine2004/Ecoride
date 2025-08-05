// --------------Utilisées dans la classe FormManager -------------------

// Fonction pour protection contre injections html et XSS
export function escapeHTML(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
    "$": "&#36;",
    "%": "&#37;",
    "=": "&#61;",
    "(": "&#40;",
    ")": "&#41;",
  };
  // console.log(map["$"]); // Devrait afficher : &#36;
  text = String(text); // converti le texte en chaine de caractère
  return text.replace(/[&<>"'$%=()]/g, (m) => map[m]);
}

// Fonction pour afficher les erreurs dans la div correspondante
export function showError(id, message) {
  const errorDiv = document.getElementById(`error-${id}`);
  if (errorDiv) {
    errorDiv.textContent = escapeHTML(message);
    errorDiv.style.display = "flex";
  }
}

// Fonction pour réinitialiser les erreurs
export function clearErrors(id) {
  const errorDiv = document.getElementById(`error-${id}`);
  if (errorDiv) {
    errorDiv.textContent = "";
    errorDiv.style.display = "none";
  }
}
