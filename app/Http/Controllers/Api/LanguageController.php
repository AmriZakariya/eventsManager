<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSetting;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Get available languages
     */
    public function index()
    {
        $settings = EventSetting::first();

        return response()->json([
            'languages' => $settings->getEnabledLanguages(),
            'default' => $settings->default_language ?? 'en',
        ]);
    }

    /**
     * Get translation file for specific language
     */
    public function translations(string $languageCode)
    {
        $settings = EventSetting::first();
        $translations = $settings->getTranslationFile($languageCode);

        return response()->json([
            'language' => $languageCode,
            'translations' => $translations,
        ]);
    }

    /**
     * Get all translations for all enabled languages
     */
    public function all()
    {
        $settings = EventSetting::first();
        $languages = $settings->getEnabledLanguages();

        $allTranslations = [];

        foreach ($languages as $lang) {
            $allTranslations[$lang['code']] = [
                'name' => $lang['name'],
                'flag' => $lang['flag'],
                'translations' => $settings->getTranslationFile($lang['code']),
            ];
        }

        return response()->json([
            'languages' => $allTranslations,
            'default' => $settings->default_language ?? 'en',
        ]);
    }
}
