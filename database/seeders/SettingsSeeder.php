<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Services\SettingsService;
use App\Settings\SettingsRegistry;
use Illuminate\Database\Seeder;

/**
 * Writes the starting content so a fresh install has a shop to look at, and so
 * the admin has real rows to edit rather than empty forms.
 *
 * Existing values are left alone: reseeding must not overwrite what the
 * business has since typed in.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedHeroSlides();
    }

    private function seedSettings(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        /** @var SettingsRegistry $registry */
        $registry = app(SettingsRegistry::class);

        $seeded = [
            'contact.phone'    => '01000000000',
            'contact.whatsapp' => '01000000000',
            'contact.email'    => 'hello@hoor.eg',

            'contact.address_ar' => 'القاهرة، مصر',
            'contact.address_en' => 'Cairo, Egypt',
            'contact.hours_ar'   => 'من السبت إلى الخميس، ١٠ صباحًا – ٦ مساءً',
            'contact.hours_en'   => 'Saturday to Thursday, 10am – 6pm',

            'social.instagram' => 'https://instagram.com/hoor',
            'social.facebook'  => 'https://facebook.com/hoor',
            'social.tiktok'    => 'https://tiktok.com/@hoor',

            'about.heading_ar' => 'عن حور',
            'about.heading_en' => 'About HOOR',
            'about.intro_ar'   => 'حور علامة مصرية للدنيم المحتشم، صُممت للمرأة التي تريد قطعة تدوم.',
            'about.intro_en'   => 'HOOR is an Egyptian modest denim label, made for women who want a piece that lasts.',
            'about.body_ar'    => 'نبدأ من القماش: دنيم ثقيل يحتفظ بشكله، وقصّات تحترم الحركة والاحتشام. كل قطعة تُختبر على أجسام حقيقية قبل أن تصل إليك.',
            'about.body_en'    => 'We start with the cloth: heavy denim that holds its shape, and cuts that respect movement and modesty. Every piece is fitted on real bodies before it reaches you.',

            'contact_page.heading_ar' => 'تواصلي معنا',
            'contact_page.heading_en' => 'Get in touch',
            'contact_page.intro_ar'   => 'نرد عادةً خلال يوم عمل واحد.',
            'contact_page.intro_en'   => 'We usually reply within one working day.',

            'newsletter.heading_ar' => 'كوني أول من تعرف',
            'newsletter.heading_en' => 'Be the first to know',
            'newsletter.body_ar'    => 'أخبار الوصول الجديد والعروض، دون إزعاج.',
            'newsletter.body_en'    => 'New arrivals and offers, without the noise.',

            'seo.title_ar'       => 'حور — دنيم محتشم مصري',
            'seo.title_en'       => 'HOOR — Egyptian Modest Denim',
            'seo.description_ar' => 'دنيم محتشم مصمم في مصر. الدفع عند الاستلام، وشحن لكل المحافظات.',
            'seo.description_en' => 'Modest denim designed in Egypt. Cash on delivery, shipping to every governorate.',
        ];

        // Only what has never been set, so reseeding is safe.
        $existing = $settings->all();

        $toWrite = collect($seeded)
            ->filter(fn (mixed $value, string $key): bool => $registry->has($key) && blank($existing[$key] ?? null))
            ->all();

        if ($toWrite !== []) {
            $settings->put($toWrite);
        }
    }

    /**
     * The brand's three hero plates, as editable rows.
     */
    private function seedHeroSlides(): void
    {
        if (HeroSlide::query()->exists()) {
            return;
        }

        /*
         * The Arabic plate is named rather than left to the filename
         * convention. The convention still works, but naming it here is what
         * makes the hero's images a property of the row — which is the point
         * of the slide living in the database at all.
         */
        $slides = [
            [
                'image_path'     => 'hero/hero-1.jpg',
                'image_path_rtl' => 'hero/hero-1-rtl.jpg',
                'backdrop'    => '#CAB296',
                'eyebrow_ar'  => 'مجموعة جديدة',
                'eyebrow_en'  => 'New collection',
                'headline_ar' => 'دنيم يليق بك',
                'headline_en' => 'Denim that suits you',
            ],
            [
                'image_path'     => 'hero/hero-2.jpg',
                'image_path_rtl' => 'hero/hero-2-rtl.jpg',
                'backdrop'    => '#CCB49A',
                'eyebrow_ar'  => 'صُنع في مصر',
                'eyebrow_en'  => 'Made in Egypt',
                'headline_ar' => 'قصّات تحترم حركتك',
                'headline_en' => 'Cuts that respect movement',
            ],
            [
                'image_path'     => 'hero/hero-3.jpg',
                'image_path_rtl' => 'hero/hero-3-rtl.jpg',
                'backdrop'    => '#DDCBB5',
                'eyebrow_ar'  => 'الدفع عند الاستلام',
                'eyebrow_en'  => 'Cash on delivery',
                'headline_ar' => 'اطلبي بثقة',
                'headline_en' => 'Order with confidence',
            ],
        ];

        foreach ($slides as $position => $slide) {
            HeroSlide::create($slide + ['position' => $position, 'is_active' => true]);
        }
    }
}
