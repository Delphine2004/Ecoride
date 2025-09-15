<?php

namespace App\Utils;

class RegexPatterns
{

    public const COMMENT_REGEX = '/^[a-zA-ZÀ-ÿ0-9\s\'".,;:!?()@$%&-]{2,255}$/u';
    public const ONLY_TEXTE_REGEX = '/^[a-zA-ZÀ-ÿ\s\'-]{4,20}$/u';
    public const OLD_REGISTRATION_NUMBER = '/^[1-9]\d{0,3}\s?[A-Z]{1,3}\s?(?:0[1-9]|[1-8]\d|9[0-5]|2[AB])$/';
    public const NEW_REGISTRATION_NUMBER = '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/';

    public const FRENCH_MOBILE_PHONE = '/^[0-9]{10}$/';
    public const LOGIN = '/^[a-zA-ZÀ-ÿ0-9\s\-]{10,25}$/u';

    public const ADDRESS = '/^[0-9]{0,5}\s*[a-zA-ZÀ-ÿ\s\'-]{10,40}$/u';
    public const ZIP_CODE = '/^[0-9]{5}$/';

    public const OLD_LICENCE_NUMBER = '/^[0-9]{8,12}$/';
    public const NEW_LICENCE_NUMBER = '/^[0-9A-Z]{13}$/';

    // modifier la longueur pour 14 plus tard - modifier message dans validatePassword
    public const PASSWORD = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
}
