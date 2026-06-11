<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Locale
{
    public const SUPPORTED = ['en', 'fr', 'ar'];

    public static function fromRequest(?Request $request = null): string
    {
        $request ??= request();

        $locale = $request->user('sanctum')?->locale
            ?? $request->query('lang')
            ?? $request->query('locale')
            ?? $request->header('X-Locale')
            ?? $request->header('Accept-Language');

        return self::normalize($locale);
    }

    public static function normalize(?string $locale): string
    {
        if (!$locale) {
            return 'en';
        }

        $locale = Str::lower(Str::before(str_replace('_', '-', $locale), ','));
        $locale = Str::before($locale, '-');

        return in_array($locale, self::SUPPORTED, true) ? $locale : 'en';
    }
}
