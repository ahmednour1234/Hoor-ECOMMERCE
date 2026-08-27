<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Settings\SettingDefinition;
use App\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reading and writing site settings.
 *
 * Settings are read on every page render — the footer alone wants a phone
 * number and three social URLs — so the whole table is loaded in one query and
 * cached, rather than queried per key. A few dozen rows is small enough to hold
 * whole, and the cache is dropped on every write so a saved change is visible
 * immediately.
 */
class SettingsService
{
    private const CACHE_KEY = 'hoor.settings';

    /**
     * Values already read this request.
     *
     * The cache still costs a driver round trip, and the footer asks for four
     * settings on every page.
     *
     * @var array<string, string|null>|null
     */
    private ?array $loaded = null;

    public function __construct(private readonly SettingsRegistry $registry)
    {
    }

    /**
     * A setting's value, cast to its declared type.
     *
     * Unknown keys return the fallback rather than throwing: a template asking
     * for a setting that has not been declared is a bug, but not one worth
     * taking the storefront down for.
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        $definition = $this->registry->get($key);

        if ($definition === null) {
            return $fallback;
        }

        $stored = $this->raw()[$key] ?? null;

        $value = $definition->cast($stored);

        // "Set, but empty" is a deliberate choice — an admin clearing the
        // WhatsApp number means "we have none", not "use the default".
        if ($stored === null && $fallback !== null) {
            return $fallback;
        }

        return $value;
    }

    /**
     * A setting in the current locale.
     *
     * Bilingual settings are stored as sibling keys (`about.heading_ar` and
     * `_en`), matching how every other translatable field in the application
     * works. Falls back to the other locale rather than showing nothing: a
     * half-translated About page still reads.
     */
    public function translated(string $key, mixed $fallback = null): mixed
    {
        $locale = app()->getLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        $value = $this->get("{$key}_{$locale}");

        if (filled($value)) {
            return $value;
        }

        $fallbackValue = $this->get("{$key}_{$other}");

        return filled($fallbackValue) ? $fallbackValue : $fallback;
    }

    public function boolean(string $key, bool $fallback = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $fallback : (bool) $value;
    }

    /**
     * Every setting for one group, keyed by its short name.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return $this->registry->group($group)
            ->mapWithKeys(fn (SettingDefinition $definition): array => [
                $definition->key => $this->get($definition->key),
            ])
            ->all();
    }

    /**
     * Everything, for a page that needs many settings at once.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $raw = $this->raw();

        return collect($this->registry->all())
            ->map(fn (SettingDefinition $definition): mixed => $definition->cast($raw[$definition->key] ?? null))
            ->all();
    }

    /**
     * Write a batch of settings.
     *
     * Batched deliberately: the admin form saves a whole panel at once, and one
     * upsert beats a query per field. Unknown keys are dropped rather than
     * stored, so a crafted post cannot invent settings.
     *
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        $rows = [];

        foreach ($values as $key => $value) {
            $definition = $this->registry->get($key);

            if ($definition === null) {
                continue;
            }

            $rows[] = [
                'key'        => $key,
                'value'      => $definition->serialise($value),
                'type'       => $definition->type,
                'group'      => $definition->group,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        DB::transaction(function () use ($rows): void {
            Setting::query()->upsert($rows, ['key'], ['value', 'type', 'group', 'updated_at']);
        });

        $this->flush();
    }

    public function set(string $key, mixed $value): void
    {
        $this->put([$key => $value]);
    }

    /**
     * Forget a setting, so it falls back to its default again.
     */
    public function forget(string $key): void
    {
        Setting::query()->whereKey($key)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        $this->loaded = null;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The whole table, as stored strings.
     *
     * @return array<string, string|null>
     */
    private function raw(): array
    {
        return $this->loaded ??= Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );
    }
}
