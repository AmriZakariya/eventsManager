<?php

namespace App\Models\Concerns;

use App\Support\Locale;

trait HasLocalizedContent
{
    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale = Locale::normalize($locale);
        $translations = $this->translationValues($field);

        $value = $translations[$locale] ?? null;

        if ($this->filledTranslation($value)) {
            return $value;
        }

        $baseValue = $this->getAttribute($field);
        if ($this->filledTranslation($baseValue)) {
            return $baseValue;
        }

        $englishValue = $translations['en'] ?? null;
        if ($this->filledTranslation($englishValue)) {
            return $englishValue;
        }

        foreach (Locale::SUPPORTED as $supportedLocale) {
            $fallback = $translations[$supportedLocale] ?? null;
            if ($this->filledTranslation($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    public function localizedPlainText(string $field, ?string $locale = null): ?string
    {
        return $this->plainText($this->localized($field, $locale));
    }

    public function translationInput(string $field): array
    {
        $translations = $this->translationValues($field);
        $translations['en'] = $translations['en'] ?? $this->getAttribute($field);

        return collect(Locale::SUPPORTED)
            ->mapWithKeys(fn (string $locale) => [$locale => $translations[$locale] ?? null])
            ->all();
    }

    public function hydrateTranslationInputs(array $fields): void
    {
        foreach ($fields as $field) {
            $this->setAttribute("{$field}_translations", $this->translationInput($field));
        }
    }

    protected function translationValues(string $field): array
    {
        $translations = $this->getAttribute("{$field}_translations");

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?: [];
        }

        return is_array($translations) ? $translations : [];
    }

    protected function filledTranslation(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : filled($value);
    }

    public function plainText(?string $value): ?string
    {
        return static::normalizePlainText($value);
    }

    protected static function normalizePlainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/<\s*br\s*\/?>/i', "\n", $value);
        $value = preg_replace('/<\s*\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace("/[ \t]+\n/", "\n", $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value);

        return trim($value);
    }

    public function prepareTranslatedData(array $data, array $fields, array $plainTextFields = []): array
    {
        return static::prepareTranslations($data, $fields, $this, $plainTextFields);
    }

    public static function prepareTranslations(
        array $data,
        array $fields,
        ?object $existingModel = null,
        array $plainTextFields = []
    ): array
    {
        foreach ($fields as $field) {
            $translationKey = "{$field}_translations";
            $translations = static::extractTranslationPayload($data, $translationKey);
            $existingTranslations = $existingModel && method_exists($existingModel, 'translationInput')
                ? $existingModel->translationInput($field)
                : [];

            if (!is_array($translations)) {
                $translations = [];
            }

            $translations = collect(Locale::SUPPORTED)
                ->mapWithKeys(function (string $locale) use ($translations, $existingTranslations, $field, $plainTextFields, $existingModel) {
                    $value = array_key_exists($locale, $translations)
                        ? $translations[$locale]
                        : ($existingTranslations[$locale] ?? null);
                    $value = is_string($value) ? trim($value) : $value;
                    $value = in_array($field, $plainTextFields, true)
                        ? static::normalizePlainText($value)
                        : $value;

                    return [$locale => $value === '' ? null : $value];
                })
                ->all();

            if (filled($translations['en'] ?? null)) {
                $data[$field] = $translations['en'];
            } elseif (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                $data[$field] = in_array($field, $plainTextFields, true)
                    ? static::normalizePlainText($data[$field])
                    : $data[$field];
                $translations['en'] = $data[$field] !== '' ? $data[$field] : null;
            }

            $data[$translationKey] = $translations;
        }

        return $data;
    }

    protected static function extractTranslationPayload(array $data, string $translationKey): array
    {
        $translations = $data[$translationKey] ?? [];

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?: [];
        }

        if (is_array($translations) && $translations !== []) {
            return $translations;
        }

        $flattened = [];

        foreach (Locale::SUPPORTED as $locale) {
            $flatKey = "{$translationKey}.{$locale}";

            if (array_key_exists($flatKey, $data)) {
                $flattened[$locale] = $data[$flatKey];
            }
        }

        return $flattened !== [] ? $flattened : (is_array($translations) ? $translations : []);
    }
}
