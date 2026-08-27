<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A customer's saved address book.
 *
 * The one rule worth centralising: exactly one address is the default. Making
 * a new one default has to unset the previous in the same breath, or the
 * checkout form has two answers to which address to prefill.
 */
class CustomerAddressService
{
    /**
     * @return Collection<int, CustomerAddress>
     */
    public function forCustomer(User $user): Collection
    {
        return $user->addresses()
            ->with(['governorate', 'area'])
            ->defaultFirst()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $data): CustomerAddress {
            // The first address saved is the default whether or not she ticked
            // the box: an address book of one has an obvious answer.
            $isFirst = $user->addresses()->count() === 0;
            $wantsDefault = (bool) ($data['is_default'] ?? false) || $isFirst;

            if ($wantsDefault) {
                $this->clearDefault($user);
            }

            return $user->addresses()->create(
                array_merge($data, ['is_default' => $wantsDefault]),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($address, $data): CustomerAddress {
            $wantsDefault = (bool) ($data['is_default'] ?? false);

            if ($wantsDefault) {
                $this->clearDefault($address->user, except: $address->id);
            }

            // Unticking the box on the only default would leave the book with
            // none, so the flag is kept until another address claims it.
            $data['is_default'] = $wantsDefault || $address->is_default;

            $address->update($data);

            return $address->refresh();
        });
    }

    /**
     * Remove an address, promoting another to default if this was it.
     */
    public function delete(CustomerAddress $address): void
    {
        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $user = $address->user;

            $address->delete();

            if ($wasDefault) {
                $user->addresses()->oldest()->first()?->update(['is_default' => true]);
            }
        });
    }

    public function makeDefault(CustomerAddress $address): CustomerAddress
    {
        return DB::transaction(function () use ($address): CustomerAddress {
            $this->clearDefault($address->user, except: $address->id);

            $address->update(['is_default' => true]);

            return $address->refresh();
        });
    }

    private function clearDefault(User $user, ?int $except = null): void
    {
        $user->addresses()
            ->where('is_default', true)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->update(['is_default' => false]);
    }
}
