import { escapeHTML, showError, clearErrors } from "./utils";
import {
  isFreeTextValide,
  isOnlyTextValide,
  isNumberValide,
  isEmailValide,
  isPasswordValide,
  isDatevalide,
  isDateFormatValide,
  isTimeFormatValide,
} from "./validations";

export class FormManager {
  constructor(form) {
    this.form = form;
  }

  isEmpty(value, id) {
    if (value.trim() === "") {
      showError(id, "Champ obligatoire.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  sanitize(value) {
    return escapeHTML(value.trim());
  }

  validateFreeText(text, id) {
    if (!this.isEmpty(text, id)) {
      return false;
    } else if (!isFreeTextValide(text)) {
      showError(id, "Caractères non autorisés.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateOnlyText(text, id) {
    if (!this.isEmpty(text, id)) {
      return false;
    } else if (!isOnlyTextValide(text)) {
      showError(id, "Seules les lettres sont autorisées.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateNumber(number, id) {
    if (!this.isEmpty(number, id)) {
      return false;
    } else if (!isNumberValide(number)) {
      showError(id, "Mauvais format.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateEmail(email, id) {
    if (!this.isEmpty(email, id)) {
      return false;
    } else if (!isEmailValide(email)) {
      showError(id, "Ne respecte pas le format email.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validatePassword(password, id) {
    if (!this.isEmpty(password, id)) {
      return false;
    } else if (!isPasswordValide(password)) {
      showError(id, "Ne respecte pas le format sécurisé.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateConfirmedPassword(password, confirmPassword, id) {
    if (!this.isEmpty(password, id) || !this.isEmpty(confirmPassword, id)) {
      return false;
    }
    if (password !== confirmPassword) {
      showError(id, "Les mots de passe ne sont pas identiques.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateDate(date, id) {
    if (!this.isEmpty(date, id)) {
      return false;
    } else if (!isDateFormatValide(date)) {
      showError(id, "Format date incorrecte.");
      return false;
    } else if (!isDatevalide(date)) {
      showError(id, "Date invalide.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

  validateTime(time, id) {
    if (!this.isEmpty(time, id)) {
      return false;
    } else if (!isTimeFormatValide(time)) {
      showError(id, "Format heure incorrecte (HH:MM sous 24heures).");
      return false;
    } else if (!isDatevalide(time)) {
      showError(id, "Heure invalide.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }

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

  getCleanInputs(inputs) {
    const cleanInputs = {};
    for (const [key, input] of Object.entries(inputs)) {
      cleanInputs[key] = this.sanitize(input.value);
    }
    return cleanInputs;
  }
}
//************************************ */
/*
  validateSelect(value, id) {
    if (!value || value === "default") {
      showError(id, "Veuillez sélectionner une option.");
      return false;
    } else {
      clearErrors(id);
      return true;
    }
  }
  */
