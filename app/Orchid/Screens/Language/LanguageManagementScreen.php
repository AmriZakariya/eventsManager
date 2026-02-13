<?php

namespace App\Orchid\Screens\Language;

use App\Models\EventSetting;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class LanguageManagementScreen extends Screen
{
    public $languageCode;
    public $settings;
    public $translations;

    public function query(Request $request): array
    {
        $settings = EventSetting::firstOrCreate([]);
        $languageCode = $request->route('languageCode') ?? $request->get('languageCode', 'en');

        // Get translations as simple key-value array
        $translationData = $settings->getTranslationFile($languageCode);

        // Convert to indexed array format for the form
        $translations = [];
        foreach ($translationData as $key => $value) {
            $translations[] = [
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : (string)$value
            ];
        }

        return [
            'settings' => $settings,
            'languageCode' => $languageCode,
            'translations' => $translations,
            'available_languages' => $settings->available_languages ?? $this->getDefaultLanguages(),
        ];
    }

    public function name(): ?string
    {
        return 'Language & Translations';
    }

    public function description(): ?string
    {
        return 'Manage app languages and edit translation files';
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('Save Changes'))
                ->icon('bs.check-circle-fill')
                ->method('saveChanges')
                ->type(Color::SUCCESS)
                ->canSee($this->languageCode !== null),

            Button::make(__('Add Language'))
                ->icon('bs.plus-circle')
                ->modal('addLanguageModal')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::view('orchid.language.banner'),

            Layout::tabs([
                'Languages' => $this->getLanguagesTab(),
                'Translations' => $this->getTranslationsTab(),
            ]),

            $this->getAddLanguageModal(),
        ];
    }

    protected function getLanguagesTab(): array
    {
        return [
            Layout::rows([
                Select::make('settings.language')
                    ->title('Default App Language')
                    ->options($this->getLanguageOptions())
                    ->help('Primary language shown on first app launch'),
            ]),

            Layout::view('orchid.language.list', [
                'languages' => $this->query(request())['available_languages'],
                'settings' => $this->query(request())['settings'],
            ]),
        ];
    }

    protected function getTranslationsTab(): array
    {
        return [
            Layout::rows([
                Select::make('languageCode')
                    ->title('Select Language to Edit')
                    ->options($this->getLanguageOptions())
                    ->value($this->query(request())['languageCode'])
                    ->help('Choose a language file to edit')
                    ->required(),
            ]),

            Layout::view('orchid.language.editor', [
                'translations' => $this->query(request())['translations'],
                'languageCode' => $this->query(request())['languageCode'],
            ]),
        ];
    }

    protected function getAddLanguageModal()
    {
        return Layout::modal('addLanguageModal', [
            Layout::rows([
                Input::make('new_language.code')
                    ->title('Language Code')
                    ->placeholder('es')
                    ->help('ISO 639-1 code (2 letters)')
                    ->maxlength(2)
                    ->required(),

                Input::make('new_language.name')
                    ->title('Language Name')
                    ->placeholder('Español')
                    ->required(),

                Input::make('new_language.flag')
                    ->title('Flag Emoji')
                    ->placeholder('🇪🇸')
                    ->required(),
            ]),
        ])->title('Add New Language')
            ->applyButton('Add Language')
            ->closeButton('Cancel');
    }

    protected function getLanguageOptions(): array
    {
        $languages = $this->query(request())['available_languages'];
        $options = [];
        foreach ($languages as $lang) {
            $options[$lang['code']] = ($lang['flag'] ?? '') . ' ' . $lang['name'];
        }
        return $options;
    }

    protected function getDefaultLanguages(): array
    {
        return [
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧', 'enabled' => true],
            ['code' => 'es', 'name' => 'Español', 'flag' => '🇪🇸', 'enabled' => false],
            ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷', 'enabled' => false],
        ];
    }

    public function saveChanges(Request $request)
    {
        $settings = EventSetting::firstOrCreate([]);

        // Save default language if changed
        if ($request->has('settings.language')) {
            $settings->language = $request->input('settings.language');
        }

        // Save translations if provided
        $languageCode = $request->input('languageCode');
        $translationsInput = $request->input('translations', []);

        if ($languageCode && !empty($translationsInput)) {
            // Convert array format to key-value pairs
            $translations = [];
            foreach ($translationsInput as $item) {
                if (!empty($item['key']) && isset($item['value'])) {
                    $translations[$item['key']] = $item['value'];
                }
            }

            // Save translation file
            $success = $settings->saveTranslationFile($languageCode, $translations);

            if ($success) {
                Toast::success('Translations saved successfully!');
            } else {
                Toast::error('Failed to save translations');
                return back();
            }
        }

        $settings->save();
        Toast::success('Changes saved successfully!');

        return redirect()->route('platform.language.management', ['languageCode' => $languageCode]);
    }

    public function toggleLanguage(Request $request)
    {
        $settings = EventSetting::firstOrCreate([]);
        $languageCode = $request->input('code');
        $enabled = $request->input('enabled');

        $languages = $settings->available_languages ?? $this->getDefaultLanguages();

        foreach ($languages as &$lang) {
            if ($lang['code'] === $languageCode) {
                $lang['enabled'] = $enabled;
                break;
            }
        }

        $settings->available_languages = $languages;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Language status updated successfully'
        ]);
    }

    public function addLanguage(Request $request)
    {
        $validated = $request->validate([
            'new_language.code' => 'required|string|size:2',
            'new_language.name' => 'required|string|max:100',
            'new_language.flag' => 'required|string|max:10',
        ]);

        $settings = EventSetting::firstOrCreate([]);
        $languages = $settings->available_languages ?? $this->getDefaultLanguages();

        // Check if language already exists
        foreach ($languages as $lang) {
            if ($lang['code'] === $request->input('new_language.code')) {
                Toast::error('Language code already exists!');
                return back();
            }
        }

        $languages[] = [
            'code' => $request->input('new_language.code'),
            'name' => $request->input('new_language.name'),
            'flag' => $request->input('new_language.flag'),
            'enabled' => true,
        ];

        $settings->available_languages = $languages;
        $settings->save();

        // Create empty translation file
        $settings->saveTranslationFile($request->input('new_language.code'), [
            'welcome' => 'Welcome',
            'app_name' => 'Event App',
        ]);

        Toast::success('Language added successfully!');

        return redirect()->route('platform.language.management', [
            'languageCode' => $request->input('new_language.code')
        ]);
    }

    public function exportTranslations(Request $request, $languageCode)
    {
        $settings = EventSetting::firstOrCreate([]);
        $translations = $settings->getTranslationFile($languageCode);

        $filename = "translations_{$languageCode}_" . date('Y-m-d') . ".json";

        return response()->json($translations, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function deleteLanguage(Request $request, $languageCode)
    {
        $settings = EventSetting::firstOrCreate([]);

        // Prevent deleting default language
        if ($languageCode === $settings->language) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default language'
            ], 400);
        }

        $languages = $settings->available_languages ?? [];
        $languages = array_filter($languages, fn($lang) => $lang['code'] !== $languageCode);

        $settings->available_languages = array_values($languages);
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted successfully'
        ]);
    }
}
