<?php

declare(strict_types=1);

namespace App\Settings;

/**
 * One setting, described once.
 *
 * The definition is the single source of truth for a setting's type, default,
 * validation and how it renders. Adding a setting means adding one entry to the
 * registry — not a migration, a validation rule, a form field and a Blade edit,
 * each of which could disagree with the others.
 */
final class SettingDefinition
{
    /**
     * @param  string  $key      dot-namespaced, e.g. contact.whatsapp
     * @param  string  $type     string|text|boolean|integer|url|email|phone
     * @param  string  $group    which admin panel it appears in
     * @param  list<string>  $rules  extra validation beyond what the type implies
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type = 'string',
        public readonly string $group = 'general',
        public readonly mixed $default = null,
        public readonly array $rules = [],
        public readonly bool $translatable = false,
    ) {
    }

    /**
     * Cast a stored string back to its real type.
     *
     * Everything lives in a text column, so without this a boolean toggle
     * returns "0" — which is truthy in PHP, and would silently switch on every
     * section an admin had switched off.
     */
    public function cast(?string $stored): mixed
    {
        if ($stored === null) {
            return $this->default;
        }

        return match ($this->type) {
            'boolean' => filter_var($stored, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $stored,
            default   => $stored,
        };
    }

    /**
     * Flatten a value for storage.
     */
    public function serialise(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            default   => (string) $value,
        };
    }

    /**
     * Validation rules, derived from the type and extended per setting.
     *
     * @return list<string>
     */
    public function validationRules(): array
    {
        $base = match ($this->type) {
            'boolean' => ['nullable', 'boolean'],
            'integer' => ['nullable', 'integer'],
            'url'     => ['nullable', 'url', 'max:255'],
            'email'   => ['nullable', 'email', 'max:190'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'text'    => ['nullable', 'string', 'max:2000'],
            default   => ['nullable', 'string', 'max:255'],
        };

        return array_values(array_unique(array_merge($base, $this->rules)));
    }

    /**
     * Which form control renders this.
     */
    public function control(): string
    {
        return match ($this->type) {
            'boolean' => 'toggle',
            'text'    => 'textarea',
            default   => 'input',
        };
    }

    /**
     * The label and hint come from translation files, keyed by the setting
     * name, so a new setting is bilingual by adding two lines.
     */
    public function label(): string
    {
        return __('settings.fields.'.$this->key);
    }

    public function hint(): ?string
    {
        $key = 'settings.hints.'.$this->key;
        $hint = __($key);

        return $hint === $key ? null : $hint;
    }
}
