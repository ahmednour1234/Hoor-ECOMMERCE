<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\SettingsService;

/**
 * The shop's contact details, as links a template can use directly.
 *
 * Blade should never compose a wa.me URL or strip a phone number itself:
 * doing it here means the stored value stays a plain phone number — which is
 * what an admin will type — and the link format can change in one place.
 */
final class StoreContact
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function phone(): ?string
    {
        return $this->settings->get('contact.phone') ?: null;
    }

    /**
     * A tel: link, with everything a dialler cannot use removed.
     */
    public function phoneLink(): ?string
    {
        $phone = $this->phone();

        return $phone === null ? null : 'tel:'.$this->dialable($phone);
    }

    public function whatsapp(): ?string
    {
        return $this->settings->get('contact.whatsapp') ?: null;
    }

    /**
     * A wa.me link.
     *
     * WhatsApp wants the number in international form with no punctuation and
     * no leading zero, so a local 010… becomes 2010….
     */
    public function whatsappLink(?string $message = null): ?string
    {
        $number = $this->whatsapp();

        if ($number === null) {
            return null;
        }

        $digits = $this->internationalDigits($number);

        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits.($message !== null ? '?text='.rawurlencode($message) : '');
    }

    public function email(): ?string
    {
        return $this->settings->get('contact.email') ?: null;
    }

    public function address(): ?string
    {
        return $this->settings->translated('contact.address') ?: null;
    }

    public function hours(): ?string
    {
        return $this->settings->translated('contact.hours') ?: null;
    }

    /**
     * Social profiles that have actually been filled in.
     *
     * Empty ones are dropped rather than rendered as dead links — a footer icon
     * pointing nowhere is worse than one absent icon.
     *
     * @return array<string, string>
     */
    public function socials(): array
    {
        return collect(['instagram', 'facebook', 'tiktok'])
            ->mapWithKeys(fn (string $network): array => [
                $network => (string) ($this->settings->get('social.'.$network) ?? ''),
            ])
            ->filter(fn (string $url): bool => $url !== '')
            ->all();
    }

    /**
     * Digits a phone dialler can use, keeping a leading +.
     */
    private function dialable(string $phone): string
    {
        $phone = EgyptianPhone::toLatinDigits($phone);

        $plus = str_starts_with(trim($phone), '+') ? '+' : '';

        return $plus.preg_replace('/\D/', '', $phone);
    }

    /**
     * An Egyptian number in international form, digits only.
     */
    private function internationalDigits(string $phone): string
    {
        $digits = preg_replace('/\D/', '', EgyptianPhone::toLatinDigits($phone)) ?? '';

        if ($digits === '') {
            return '';
        }

        /*
         * Egypt's country code is 20, and a local number starts with a single
         * 0 that the international form replaces: 01012345678 becomes
         * 201012345678.
         *
         * Only one leading zero is dropped — 010 is a real prefix, and
         * trimming every zero would eat the 1 that follows it.
         */
        if (str_starts_with($digits, '20')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '20'.substr($digits, 1);
        }

        return '20'.$digits;
    }
}
