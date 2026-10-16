<?php

namespace App\Models;

/** English text kept in the "en" JSON column; tr('field') gives it on the English website, otherwise the Bangla field. */
trait Translatable
{
    public function initializeTranslatable(): void
    {
        $this->mergeCasts(['en' => 'array']);
    }

    public function tr(string $field): ?string
    {
        if (app()->getLocale() === 'en' && filled($this->en[$field] ?? null)) {
            return $this->en[$field];
        }

        return $this->{$field};
    }

    /** English values from an admin form's en[field] inputs (null when the form had none). */
    public static function englishInput(\Illuminate\Http\Request $request, array $fields): ?array
    {
        if (! $request->has('en')) {
            return null;
        }
        $request->validate(['en' => ['array'], 'en.*' => ['nullable', 'string', 'max:3000']]);

        return collect($fields)->mapWithKeys(fn ($f) => [$f => trim((string) $request->input("en.$f"))])->filter(fn ($v) => $v !== '')->all();
    }

    /** Value of an English field for admin forms. */
    public function enValue(string $field): string
    {
        return (string) ($this->en[$field] ?? '');
    }
}
