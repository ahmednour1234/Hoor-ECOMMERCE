<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * The questions customers actually ask a Cash-on-Delivery denim shop, plus the
 * location details the contact page shows.
 *
 * Existing rows are left alone, so reseeding never overwrites what the business
 * has since edited.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFaqs();
        $this->seedLocation();
    }

    private function seedFaqs(): void
    {
        if (Faq::query()->placement('contact')->exists()) {
            return;
        }

        $faqs = [
            [
                'question_en' => 'How can I track my order?',
                'question_ar' => 'كيف أتتبع طلبي؟',
                'answer_en'   => 'Enter your order number and the phone number you ordered with on the Track Order page. No account is needed.',
                'answer_ar'   => 'أدخلي رقم الطلب ورقم الهاتف الذي طلبتِ به في صفحة تتبع الطلب. لا حاجة إلى حساب.',
            ],
            [
                'question_en' => 'What is your return policy?',
                'question_ar' => 'ما هي سياسة الإرجاع؟',
                'answer_en'   => 'You may request a return or exchange within 14 days of delivery, as long as the piece is unworn and in its original condition.',
                'answer_ar'   => 'يمكنك طلب الإرجاع أو الاستبدال خلال ١٤ يومًا من الاستلام، بشرط أن تكون القطعة غير مستعملة وبحالتها الأصلية.',
            ],
            [
                'question_en' => 'How long does delivery take?',
                'question_ar' => 'كم يستغرق التوصيل؟',
                'answer_en'   => 'Cairo and Giza usually arrive within 2 to 3 working days. Other governorates take 3 to 5 working days.',
                'answer_ar'   => 'القاهرة والجيزة عادةً خلال ٢ إلى ٣ أيام عمل. باقي المحافظات من ٣ إلى ٥ أيام عمل.',
            ],
            [
                'question_en' => 'Do you deliver to every governorate?',
                'question_ar' => 'هل توصلون إلى كل المحافظات؟',
                'answer_en'   => 'Yes. We deliver across Egypt, and the shipping fee for your governorate is shown at checkout before you confirm.',
                'answer_ar'   => 'نعم، نوصل إلى جميع أنحاء مصر، ويظهر سعر الشحن لمحافظتك عند إتمام الطلب قبل التأكيد.',
            ],
            [
                'question_en' => 'Can I change or cancel my order?',
                'question_ar' => 'هل يمكنني تعديل أو إلغاء طلبي؟',
                'answer_en'   => 'Yes, while the order is still awaiting confirmation. Call us with your order number and we will take care of it.',
                'answer_ar'   => 'نعم، ما دام الطلب في انتظار التأكيد. اتصلي بنا واذكري رقم طلبك وسنتولى الأمر.',
            ],
            [
                'question_en' => 'What payment methods do you accept?',
                'question_ar' => 'ما طرق الدفع المتاحة؟',
                'answer_en'   => 'Cash on delivery only. You pay the courier when your order reaches you, so nothing is charged in advance.',
                'answer_ar'   => 'الدفع عند الاستلام فقط. تدفعين لمندوب التوصيل عند وصول طلبك، فلا يُخصم منك شيء مقدمًا.',
            ],
        ];

        foreach ($faqs as $position => $faq) {
            Faq::create($faq + [
                'placement' => 'contact',
                'position'  => $position,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Location details, only where nothing has been set.
     */
    private function seedLocation(): void
    {
        /** @var SettingsService $settings */
        $settings = app(SettingsService::class);

        $existing = $settings->all();

        $seeded = [
            'contact.address_ar'   => 'شارع النيل ١٢، المعادي، القاهرة',
            'contact.address_en'   => '12 Nile Street, Maadi, Cairo',
            'contact.hours_ar'     => 'السبت – الخميس، ١٠ صباحًا – ٨ مساءً',
            'contact.hours_en'     => 'Saturday to Thursday, 10am – 8pm',
            'contact.hours_alt_ar' => 'الجمعة، ٢ ظهرًا – ٨ مساءً',
            'contact.hours_alt_en' => 'Friday, 2pm – 8pm',
            'contact.response_ar'  => 'نرد عادةً خلال يوم عمل واحد.',
            'contact.response_en'  => 'We usually reply within one working day.',
            'contact.map_url'      => 'https://maps.google.com/?q=Maadi,Cairo,Egypt',
        ];

        $toWrite = collect($seeded)
            ->filter(fn (mixed $value, string $key): bool => blank($existing[$key] ?? null))
            ->all();

        if ($toWrite !== []) {
            $settings->put($toWrite);
        }
    }
}
