import {
  isFreeTextValide,
  isOnlyTextValide,
  isNumberValide,
  isEmailValide,
  isPasswordValide,
  isDateValide,
  isDateFormatValide,
  isTimeFormatValide,
} from "./validations.js";

export class FormManager {
  constructor(form) {
    this.form = form;
    this.errors = {};
  }

  //Fonctions qui gérent l'affichage des div errors
  showError(message, fields) {
    fields.forEach((field) => {
      this.errors[field] = message;
      const input = this.form.querySelector(`[name="${field}"], #${field}`);
      if (input) input.classList.add("input-error");
    });
    this.updateFeedback();
  }

  clearErrors(fields) {
    fields.forEach((field) => {
      delete this.errors[field];
      const input = this.form.querySelector(`[name="${field}"], #${field}`);
      if (input) input.classList.remove("input-error");
    });
    this.updateFeedback();
  }

  updateFeedback() {
    const errorDiv = document.getElementById("feedback-form");
    const messages = Object.values(this.errors).filter(Boolean);
    if (messages.length) {
      errorDiv.innerHTML = messages.join(" <br> ");
      errorDiv.style.display = "flex";
    } else {
      errorDiv.textContent = "";
      errorDiv.style.display = "none";
    }
  }

  // Méthode qui vérifie si un champ obligatoire est rempli
  isEmpty(value, id) {
    if (value.trim() === "") {
      // Trouver le label associé au champ
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;

      this.showError(`« ${fieldName} » est obligatoire.`, [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  //Méthodes qui vérifie si les champs textes sont propres
  validateFreeText(text, id) {
    if (!this.isEmpty(text, id)) {
      return false;
    } else if (!isFreeTextValide(text)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(
        `« ${fieldName} » contient des caractères non autorisés.`,
        [id]
      );
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  validateOnlyText(text, id) {
    if (!this.isEmpty(text, id)) {
      return false;
    } else if (!isOnlyTextValide(text)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(`« ${fieldName} » doit contenir uniquement des lettres.`, [
        id,
      ]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  /* Peut être créer une fonction dans formManager pour valider le prix et le nombre de place?? */

  validateNumber(number, id) {
    if (!this.isEmpty(number, id)) {
      return false;
    } else if (!isNumberValide(number)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(`« ${fieldName} » doit être supérieure à 0.`, [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  // Méthode qui vérifie l'email
  validateEmail(email, id) {
    if (!this.isEmpty(email, id)) {
      return false;
    } else if (!isEmailValide(email)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(`« ${fieldName} » ne respecte pas le format email.`, [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  // Méthodes qui vérifie les mots de passe
  validatePassword(password, id) {
    if (!this.isEmpty(password, id)) {
      return false;
    } else if (!isPasswordValide(password)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(`« ${fieldName} » ne respecte pas le format sécurisé.`, [
        id,
      ]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  validateConfirmedPassword(password, confirmPassword, id) {
    if (!this.isEmpty(password, id) || !this.isEmpty(confirmPassword, id)) {
      return false;
    }
    if (password !== confirmPassword) {
      this.showError("Les mots de passe ne sont pas identiques.", [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  // Méthodes qui vérifient la date et l'heure - A VERIFIER
  validateDate(date, id) {
    // Vérifie si c'est vide
    if (!this.isEmpty(date, id)) {
      return false;
    }
    // Vérifie si le format est bon
    if (!isDateFormatValide(date)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(`« ${fieldName} » n'est pas au bon format.`, [id]);
      return false;
    } else if (!isDateValide(date)) {
      this.showError("Merci de selectionner une date à venir.", [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  validateTime(time, timeId) {
    // Vérifie si c'est vide
    if (!this.isEmpty(time, timeId)) {
      return false;
    }
    // vérifie si l'heure respecte le format
    if (!isTimeFormatValide(time)) {
      const label = this.form.querySelector(`label[for="${id}"]`);
      const fieldName = label ? label.textContent.trim() : id;
      this.showError(
        `« ${fieldName} » n'est pas au bon format (HH:MM sous 24heures).`,
        [timeId]
      );
      return false;
    }
  }

  // Methode qui vérifie les listes
  validateSelect(value, id) {
    if (!this.isEmpty(value, id)) {
      return false;
    } else if (!value || value === "default") {
      this.showError("Veuillez sélectionner une option.", [id]);
      return false;
    } else {
      this.clearErrors([id]);
      return true;
    }
  }

  // Méthode qui centralise les vérifications en fonction des types
  validateInputs(value, id, type) {
    // console.log(`Validation en cours pour ${id} avec type ${type}`);

    switch (type) {
      case "onlytext":
        return this.validateOnlyText(value, id);
      case "freetext":
        return this.validateFreeText(value, id);
      case "email":
        return this.validateEmail(value, id);
      case "number":
        return this.validateNumber(value, id);
      case "password":
        return this.validatePassword(value, id);
      case "date":
        return this.validateDate(value, id);
      case "time":
        return this.validateTime(value, id);
      case "select-one":
        return this.validateSelect(value, id);
      default:
        console.warn(`Type non géré : ${type}`);
        return true;
    }
  }

  validateForm(inputs) {
    let isValid = true;

    for (const [key, input] of Object.entries(inputs)) {
      const value = input.value;
      const type = input.dataset.type || input.type; // priorité à data-type si présent
      const result = this.validateInputs(value, input.id, type);

      if (!result) {
        isValid = false;
      }
    }

    return isValid;
  }

  // Fonction pour protection contre injections html et XSS
  escapeHTML(text) {
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

  sanitize(value) {
    return this.escapeHTML(value.trim());
  }

  getCleanInputs(inputs) {
    const cleanInputs = {};
    for (const [key, input] of Object.entries(inputs)) {
      cleanInputs[key] = this.sanitize(input.value);
    }
    return cleanInputs;
  }
}
