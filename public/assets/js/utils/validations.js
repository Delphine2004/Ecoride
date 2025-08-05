export function isDatevalide(dateStr) {
  const date = new Date(dateStr);
  if (!isNaN(date.getTime())) {
    return true;
  } else {
    return false;
  }
}

export function isDateFormatValide(dateStr) {
  const dateRegex = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/; // format format iso fiable AAAA-MM-JJ

  const cleanDate = dateStr.trim();

  if (!dateRegex.test(cleanDate)) {
    return false;
  } else {
    return true;
  }
}

export function isTimeFormatValide(timeStr) {
  const timeRegex = /^([01]\d|2[0-3]):([0-5]\d)$/; // HH:MM 24h

  const cleanTime = timeStr.trim();

  if (!timeRegex.test(cleanTime)) {
    return false;
  } else {
    return true;
  }
}

export function isFreeTextValide(text) {
  const freetextRegex = /^[a-zA-ZÀ-ÿ0-9\s'".,;:!?()@$%&-]+$/u; //flag u (Unicode) est supporté par tous les navigateurs modernes

  const cleanFreeText = text.trim();

  if (!freetextRegex.test(cleanFreeText)) {
    return false;
  } else {
    return true;
  }
}

export function isOnlyTextValide(text) {
  const onlyTextRegex = /^[a-zA-ZÀ-ÿ\s-]+$/u;

  const cleanOnlyTextRegex = text.trim();

  if (!onlyTextRegex.test(cleanOnlyTextRegex)) {
    return false;
  } else {
    return true;
  }
}

export function isNumberValide(number) {
  const cleanNumber = parseInt(number, 10);

  if (isNaN(cleanNumber)) {
    return false;
  } else if (cleanNumber <= 0 || cleanNumber > 5) {
    return false;
  } else {
    return true;
  }
}

export function isEmailValide(email) {
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  const cleanEmail = email.trim();

  if (!emailRegex.test(cleanEmail)) {
    return false;
  } else {
    return true;
  }
}

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
