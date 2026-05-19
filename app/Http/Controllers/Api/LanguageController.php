<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LanguageController extends Controller
{
    /**
     * Get available languages
     */
    public function index()
    {
        $payload = Cache::remember('api:languages:index', now()->addMinutes(30), function () {
            $settings = EventSetting::first();

            return [
                'languages' => $settings->getEnabledLanguages(),
                'default' => $settings->default_language ?? 'en',
            ];
        });

        return response()->json($payload);
    }

    /**
     * Get translation file for specific language
     */
    public function translations(string $languageCode)
    {
        $payload = Cache::remember("api:languages:translations:{$languageCode}", now()->addMinutes(30), function () use ($languageCode) {
            $settings = EventSetting::first();
            $translations = $settings->getTranslationFile($languageCode);

            return [
                'language' => $languageCode,
                'translations' => $translations,
            ];
        });

        return response()->json($payload);
    }

    /**
     * Get all translations for all enabled languages
     */
    public function all()
    {
        $payload = Cache::remember('api:languages:all', now()->addMinutes(30), function () {
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

            return [
                'languages' => $allTranslations,
                'default' => $settings->default_language ?? 'en',
            ];
        });

        return response()->json($payload);
    }
}
