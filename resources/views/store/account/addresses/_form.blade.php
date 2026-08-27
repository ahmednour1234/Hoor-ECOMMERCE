{{--
    The address form, shared by create and edit.

    The governorate/area cascade mirrors checkout: an area only means something
    relative to its governorate, so the second list is rebuilt whenever the
    first changes. Self-contained Alpine rather than reusing checkoutPage(),
    which also drives shipping quotes and a cart summary that do not exist here.
--}}
@props(['address' => null, 'governorates'])

@php
    $selectedGovernorate = old('governorate_id', $address?->governorate_id);
    $selectedArea = old('area_id', $address?->area_id);
@endphp

<form method="POST"
      action="{{ $address
          ? route('store.account.addresses.update', $address)
          : route('store.account.addresses.store') }}"
      x-data="{
          governorates: @js($governorates->mapWithKeys(fn ($g) => [
              $g->id => $g->areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
          ])),
          governorateId: @js($selectedGovernorate ? (int) $selectedGovernorate : ''),
          areaId: @js($selectedArea ? (int) $selectedArea : ''),

          get areas() {
              return this.governorates[this.governorateId] ?? [];
          },

          onGovernorateChange() {
              // The previous area belongs to the previous governorate, so it
              // cannot survive the change.
              this.areaId = '';
          },
      }"
      class="card space-y-5 p-6">

    @csrf
    @if ($address)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger">{{ __('catalog.messages.has_errors') }}</x-ui.alert>
    @endif

    <x-ui.input name="label"
                :label="__('account.addresses.label')"
                :hint="__('account.addresses.label_hint')"
                :value="old('label', $address?->label)" />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="full_name"
                    :label="__('checkout.fields.full_name')"
                    :value="old('full_name', $address?->full_name)"
                    autocomplete="name" required class="sm:col-span-2" />

        <x-ui.input name="phone" type="tel"
                    :label="__('checkout.fields.phone')"
                    :hint="__('checkout.fields.phone_hint')"
                    :value="old('phone', $address?->phone)"
                    dir="ltr" inputmode="numeric" autocomplete="tel" required />

        <x-ui.input name="phone_alt" type="tel"
                    :label="__('checkout.fields.phone_alt')"
                    :hint="__('checkout.fields.phone_alt_hint')"
                    :value="old('phone_alt', $address?->phone_alt)"
                    dir="ltr" inputmode="numeric" />

        <x-ui.select name="governorate_id"
                     :label="__('checkout.fields.governorate')"
                     :options="$governorates->mapWithKeys(fn ($g) => [$g->id => $g->name])->all()"
                     :selected="$selectedGovernorate"
                     :placeholder="__('shipping.checkout.choose')"
                     required
                     x-model.number="governorateId"
                     @change="onGovernorateChange()" />

        <div>
            <label for="area_id" class="form-label">{{ __('checkout.fields.area_optional') }}</label>

            <select name="area_id" id="area_id" class="form-select"
                    x-model.number="areaId"
                    :disabled="!areas.length">
                <option value="">{{ __('shipping.checkout.choose_area') }}</option>
                <template x-for="area in areas" :key="area.id">
                    <option :value="area.id" x-text="area.name"></option>
                </template>
            </select>

            @error('area_id')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <x-ui.textarea name="address" rows="3"
                       :label="__('checkout.fields.address')"
                       :hint="__('checkout.fields.address_hint')"
                       :value="old('address', $address?->address)"
                       autocomplete="street-address" required class="sm:col-span-2" />

        <x-ui.input name="landmark"
                    :label="__('checkout.fields.landmark')"
                    :hint="__('checkout.fields.landmark_hint')"
                    :value="old('landmark', $address?->landmark)"
                    class="sm:col-span-2" />
    </div>

    <label class="flex items-center gap-2 text-sm text-hoor-navy-700">
        <input type="checkbox" name="is_default" value="1" class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500"
               @checked(old('is_default', $address?->is_default))>
        <span>
            {{ __('account.addresses.default') }}
            <span class="text-xs text-hoor-muted">— {{ __('account.addresses.default_hint') }}</span>
        </span>
    </label>

    <div class="flex gap-3 border-t border-hoor-cream-300 pt-5">
        <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>

        <x-ui.button variant="ghost" :href="route('store.account.addresses.index')">
            {{ __('common.actions.cancel') }}
        </x-ui.button>
    </div>
</form>
