<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SettingsService;
use App\Settings\SettingDefinition;
use App\Settings\SettingsRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Site settings.
 *
 * One screen per group, driven entirely by the registry: the form fields, their
 * validation and their storage all derive from the same definitions, so adding
 * a setting is one registry entry rather than four edits that could disagree.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SettingsRegistry $registry,
    ) {
    }

    public function edit(Request $request, ?string $group = null): View
    {
        $this->authorize('manage', \App\Models\Setting::class);

        $group ??= $this->registry->groups()[0];

        if (! in_array($group, $this->registry->groups(), strict: true)) {
            throw new NotFoundHttpException();
        }

        return view('admin.settings.edit', [
            'group'       => $group,
            'groups'      => $this->registry->groups(),
            'definitions' => $this->registry->group($group),
            'values'      => $this->settings->all(),

            // The featured-collection picker needs real categories.
            'categories' => $group === 'homepage' ? $this->categories() : collect(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $this->authorize('manage', \App\Models\Setting::class);

        $definitions = $this->registry->group($group);

        if ($definitions->isEmpty()) {
            throw new NotFoundHttpException();
        }

        /*
         * Rules come from the definitions, so a setting cannot be saved without
         * being validated, and a new setting arrives validated for free.
         *
         * Keys are dot-namespaced, which is also Laravel's nesting syntax, so
         * they are submitted under a flat `settings` array and read back by
         * their literal key.
         */
        $rules = $definitions
            ->mapWithKeys(fn (SettingDefinition $definition): array => [
                'settings.'.str_replace('.', '\\.', $definition->key) => $definition->validationRules(),
            ])
            ->all();

        $request->validate($rules, [], $this->attributeNames($definitions));

        $submitted = (array) $request->input('settings', []);

        $values = [];

        foreach ($definitions as $definition) {
            // An unticked checkbox is absent from the post entirely, so a
            // boolean must be read as "false" rather than left untouched —
            // otherwise a section could never be switched off.
            $values[$definition->key] = $definition->type === 'boolean'
                ? (bool) ($submitted[$definition->key] ?? false)
                : ($submitted[$definition->key] ?? null);
        }

        $this->settings->put($values);

        return back()->with('status', __('settings.saved'));
    }

    /**
     * Human names for the validation messages.
     *
     * @param  \Illuminate\Support\Collection<string, SettingDefinition>  $definitions
     * @return array<string, string>
     */
    private function attributeNames($definitions): array
    {
        return $definitions
            ->mapWithKeys(fn (SettingDefinition $definition): array => [
                'settings.'.str_replace('.', '\\.', $definition->key) => $definition->label(),
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categories()
    {
        return Category::query()->active()->ordered()->get(['id', 'name_ar', 'name_en']);
    }
}
