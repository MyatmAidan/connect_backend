<?php

namespace App\Support;

class Locale
{
    public static function resolve(?string $acceptLanguage = null): string
    {
        if (! $acceptLanguage) {
            return 'en';
        }

        $locale = strtolower(substr(trim(explode(',', $acceptLanguage)[0]), 0, 2));

        return in_array($locale, ['en', 'my'], true) ? $locale : 'en';
    }
}
