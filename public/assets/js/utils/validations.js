// Fonction qui vérifie si la date et l'heure saisie par l'utilisateur est bien dans le future
// Voir si dateStr contient bien l'heure sinon mettra minuit et sera faux pour jour J
export function isDateValide(dateStr) {
  const date = new Date(dateStr); // Date et heure saisies par l'utilisateur
  const now = new Date(); // Date et heure du moment

  if (!isNaN(date.getTime()) && date >= now) {
    return true;
  } else {
    return false;
  }
}

// Fonction qui vérifie si la date est au bon format - VOIR SI ELLE FONCTIONNE
export function isDateFormatValide(dateStr) {
  const dateRegex = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/; // format format iso fiable AAAA-MM-JJ
  const cleanDate = dateStr.trim();

  if (!dateRegex.test(cleanDate)) {
    return false;
  }

  const date = new Date(dateStr);
  return date instanceof Date && !isNaN(date);
}

// Fonction qui vérifie si l'heure est au bon format - VOIR SI ELLE FONCTIONNE
export function isTimeFormatValide(timeStr) {
  const timeRegex = /^([01]\d|2[0-3]):([0-5]\d)$/; // HH:MM 24h

  const cleanTime = timeStr.trim();

  if (!timeRegex.test(cleanTime)) {
    return false;
  } else {
    return true;
  }
}

// Fonction qui vérifie un texte libre (commentaire, ...)
export function isFreeTextValide(text) {
  const freetextRegex = /^[a-zA-ZÀ-ÿ0-9\s'".,;:!?()@$%&-]+$/u; //flag u (Unicode) est supporté par tous les navigateurs modernes

  const cleanFreeText = text.trim();

  if (!freetextRegex.test(cleanFreeText)) {
    return false;
  } else {
    return true;
  }
}

/* Fonction qui vérifie les champs textes (inclus les accents, les espaces, les tirets et les apostrophes) */
export function isOnlyTextValide(text) {
  const onlyTextRegex = /^[a-zA-ZÀ-ÿ\s'-]+$/u;

  const onlyText = text.trim();

  if (!onlyTextRegex.test(onlyText)) {
    return false;
  } else {
    return true;
  }
}

// Fonction qui vérifie qu'un champ est bien un nombre positif
export function isNumberValide(number) {
  const cleanNumber = parseInt(number, 10);

  if (!isNaN(cleanNumber) && cleanNumber > 0) {
    return true;
  } else {
    return false;
  }
}

// Fonction qui vérifie si l'email respecte le format email
export function isEmailValide(email) {
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  const cleanEmail = email.trim();

  if (!emailRegex.test(cleanEmail)) {
    return false;
  } else {
    return true;
  }
}

/* Fonction qui vérifie si le mot de passe respecte le format imposé
!!!!!!!!Aprés test -> modifier pour plus de 8 caractères min */
export function isPasswordValide(password) {
  const uppercaseRegex = /[A-Z]/;
  const lowercaseRegex = /[a-z]/;
  const numberRegex = /[0-9]/;
  const symbolsRegex = /[!@#$%^&*(),.?":{}|]/;

  const cleanPassword = password.trim();

  if (cleanPassword.length < 8) {
    return false;
  } else if (!uppercaseRegex.test(cleanPassword)) {
    return false;
  } else if (!lowercaseRegex.test(cleanPassword)) {
    return false;
  } else if (!numberRegex.test(cleanPassword)) {
    return false;
  } else if (!symbolsRegex.test(cleanPassword)) {
    return false;
  } else {
    return true;
  }
}
