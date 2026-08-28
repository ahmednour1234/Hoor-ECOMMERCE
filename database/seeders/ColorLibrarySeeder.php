<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A working colour library for a denim shop.
 *
 * Grouped by family and ordered so the admin list reads sensibly: every denim
 * wash first, because that is what most of the catalogue is, then neutrals,
 * then the colours a collection actually uses.
 *
 * Deliberately not a list of every named colour that exists. A shop with five
 * hundred colours has a filter nobody can scan and an admin list nobody can
 * find anything in — and a denim label will never stock "papaya whip". This is
 * the set a real range draws from, with the shades people actually type into a
 * spreadsheet.
 *
 * Existing colours are never overwritten: the shop may have edited a hex or a
 * name, and a reseed must not undo that.
 */
class ColorLibrarySeeder extends Seeder
{
    /**
     * name_en, name_ar, hex — grouped by family.
     *
     * @var array<string, list<array{0: string, 1: string, 2: string}>>
     */
    private const FAMILIES = [
        // The heart of the catalogue.
        'denim' => [
            ['Raw Indigo',      'نيلي خام',        '#1B2A4A'],
            ['Indigo',          'نيلي',            '#2B4166'],
            ['Deep Indigo',     'نيلي غامق',       '#22344F'],
            ['Rinse Wash',      'غسيل خفيف',       '#243B5E'],
            ['Dark Wash',       'أزرق غامق',       '#1E3048'],
            ['Midnight Denim',  'دنيم ليلي',       '#161F33'],
            ['Classic Blue',    'أزرق كلاسيكي',    '#35547F'],
            ['Mid Blue',        'أزرق متوسط',      '#4A73A0'],
            ['Medium Wash',     'غسيل متوسط',      '#5B82AC'],
            ['Stone Wash',      'غسيل حجري',       '#7C9AC0'],
            ['Light Wash',      'أزرق فاتح',       '#9BB6D2'],
            ['Ice Blue',        'أزرق ثلجي',       '#BBD0E4'],
            ['Bleached Denim',  'دنيم مبيّض',      '#D3E0EC'],
            ['Vintage Blue',    'أزرق قديم',       '#6E8CAE'],
            ['Faded Blue',      'أزرق باهت',       '#8CA6C4'],
            ['Acid Wash',       'غسيل أسيد',       '#A8BFD6'],
            ['Black Denim',     'دنيم أسود',       '#1C1C1E'],
            ['Washed Black',    'أسود مغسول',      '#3A3A3E'],
            ['Grey Denim',      'دنيم رمادي',      '#6B6F76'],
            ['White Denim',     'دنيم أبيض',       '#F2F2EF'],
            ['Ecru Denim',      'دنيم إكرو',       '#E6DCC9'],
        ],

        // Everything a modest range is built on.
        'neutral' => [
            ['White',           'أبيض',            '#FFFFFF'],
            ['Off White',       'أبيض مكسور',      '#F7F4EF'],
            ['Ivory',           'عاجي',            '#F5F0E1'],
            ['Cream',           'كريمي',           '#F3EADA'],
            ['Ecru',            'بيج فاتح',        '#EFE7DA'],
            ['Vanilla',         'فانيليا',         '#EFE4C8'],
            ['Bone',            'عظمي',            '#E3DCCF'],
            ['Sand',            'رملي',            '#D8C6B3'],
            ['Beige',           'بيج',             '#D9C7AE'],
            ['Nude',            'نود',             '#DEC5AE'],
            ['Latte',           'لاتيه',           '#C4A585'],
            ['Camel',           'جملي',            '#B48A5F'],
            ['Tan',             'تان',             '#A9784F'],
            ['Caramel',         'كراميل',          '#9E6B3C'],
            ['Toffee',          'توفي',            '#8A5A32'],
            ['Mocha',           'موكا',            '#7A5B45'],
            ['Coffee',          'بني قهوة',        '#5C4033'],
            ['Chocolate',       'شوكولاتة',        '#4A2F23'],
            ['Espresso',        'إسبريسو',         '#3A2A20'],
            ['Taupe',           'تاوب',            '#8C8078'],
            ['Stone',           'حجري',            '#B9B0A3'],
            ['Oatmeal',         'شوفاني',          '#D9CEBB'],
            ['Khaki',           'كاكي',            '#9A8B6B'],
            ['Light Grey',      'رمادي فاتح',      '#D2D2D2'],
            ['Silver Grey',     'رمادي فضي',       '#BFC2C5'],
            ['Grey',            'رمادي',           '#8E8E93'],
            ['Heather Grey',    'رمادي مبرقش',     '#A5A6A8'],
            ['Slate Grey',      'رمادي أردوازي',   '#6B7280'],
            ['Charcoal',        'فحمي',            '#36393D'],
            ['Graphite',        'جرافيت',          '#4B4E52'],
            ['Black',           'أسود',            '#000000'],
            ['Soft Black',      'أسود ناعم',       '#1A1A1A'],
            ['Jet Black',       'أسود نفاث',       '#0B0B0B'],
        ],

        'blue' => [
            ['Powder Blue',     'أزرق بودري',      '#B6CDE3'],
            ['Sky Blue',        'أزرق سماوي',      '#87B7DC'],
            ['Cornflower',      'أزرق ذرة',        '#6E92CE'],
            ['Denim Blue',      'أزرق دنيم',       '#4C6FA5'],
            ['Royal Blue',      'أزرق ملكي',       '#2A4FA2'],
            ['Cobalt',          'كوبالت',          '#1B45A8'],
            ['Navy',            'كحلي',            '#12274A'],
            ['Midnight Navy',   'كحلي ليلي',       '#0B1A33'],
            ['Steel Blue',      'أزرق فولاذي',     '#4C7A93'],
            ['Teal',            'تركوازي غامق',    '#1F6F78'],
            ['Petrol',          'بترولي',          '#14545C'],
            ['Turquoise',       'تركوازي',         '#2DA9A3'],
            ['Aqua',            'أكوا',            '#7FD3CE'],
            ['Mint',            'نعناعي',          '#A8DCC6'],
        ],

        'green' => [
            ['Sage',            'سيج',             '#A8B5A0'],
            ['Pistachio',       'فستقي',           '#B9CDA1'],
            ['Olive',           'زيتي',            '#5C6350'],
            ['Khaki Green',     'أخضر كاكي',       '#7A7C52'],
            ['Moss',            'طحلبي',           '#6B7A4B'],
            ['Army Green',      'أخضر عسكري',      '#4A5238'],
            ['Forest Green',    'أخضر غابي',       '#2F4A34'],
            ['Emerald',         'زمردي',           '#1F7A54'],
            ['Bottle Green',    'أخضر زجاجي',      '#12362A'],
            ['Seafoam',         'أخضر بحري',       '#C3DDD0'],
        ],

        'red' => [
            ['Blush',           'وردي خفيف',       '#EEC9C4'],
            ['Rose',            'وردي',            '#DFA0A0'],
            ['Dusty Pink',      'وردي باهت',       '#C89A9A'],
            ['Pink',            'بينك',            '#E58AA8'],
            ['Fuchsia',         'فوشيا',           '#C2367F'],
            ['Coral',           'مرجاني',          '#E8735E'],
            ['Terracotta',      'طيني',            '#B2604A'],
            ['Rust',            'صدئي',            '#9C4A2A'],
            ['Brick',           'طوبي',            '#8E3B2E'],
            ['Red',             'أحمر',            '#C62828'],
            ['Cherry',          'كرزي',            '#A11B2B'],
            ['Burgundy',        'خمري',            '#6E1F2E'],
            ['Maroon',          'عنابي',           '#5C1A25'],
            ['Wine',            'نبيتي',           '#4E1B2C'],
        ],

        'warm' => [
            ['Butter',          'زبدي',            '#F3E3B0'],
            ['Lemon',           'ليموني',          '#F2E06B'],
            ['Yellow',          'أصفر',            '#F5C518'],
            ['Mustard',         'خردلي',           '#C99A2E'],
            ['Gold',            'ذهبي',            '#B8912F'],
            ['Honey',           'عسلي',            '#D8A24A'],
            ['Apricot',         'مشمشي',           '#F0B27A'],
            ['Peach',           'خوخي',            '#F5C3A6'],
            ['Orange',          'برتقالي',         '#E8762C'],
            ['Burnt Orange',    'برتقالي محروق',   '#C0562A'],
        ],

        'purple' => [
            ['Lilac',           'ليلكي',           '#C9B6DC'],
            ['Lavender',        'لافندر',          '#B7A5D4'],
            ['Mauve',           'موف',             '#A98BA3'],
            ['Purple',          'بنفسجي',          '#6F4A9E'],
            ['Plum',            'برقوقي',          '#5B2C51'],
            ['Aubergine',       'باذنجاني',        '#3E2237'],
        ],

        'metallic' => [
            ['Silver',          'فضي',             '#C0C4C8'],
            ['Pewter',          'قصديري',          '#8F969C'],
            ['Bronze',          'برونزي',          '#8A6A3C'],
            ['Copper',          'نحاسي',           '#A9663C'],
            ['Rose Gold',       'ذهبي وردي',       '#C08A7D'],
            ['Gold Metallic',   'ذهبي معدني',      '#C9A227'],
        ],

        // Patterns are not colours, but a spreadsheet will call them one — and
        // refusing "Striped" because it has no hex helps nobody.
        'pattern' => [
            ['Striped',         'مخطط',            '#8FA3BF'],
            ['Checked',         'كاروهات',         '#9A8878'],
            ['Floral',          'مورد',            '#C9A0AE'],
            ['Printed',         'مطبوع',           '#9C9C9C'],
            ['Embroidered',     'مطرز',            '#B8A88F'],
            ['Distressed',      'ممزق',            '#7E97B5'],
            ['Multicolour',     'متعدد الألوان',   '#9E7BB5'],
        ],
    ];

    public function run(): void
    {
        $position = (int) Color::query()->max('sort_order');

        foreach (self::FAMILIES as $colours) {
            foreach ($colours as [$english, $arabic, $hex]) {
                $slug = Str::slug($english);

                /*
                 * Matched on slug rather than name, because that is what the
                 * unique index protects — and skipped rather than updated: the
                 * shop may have adjusted a hex or renamed something, and a
                 * reseed must not undo their work.
                 */
                if (Color::query()->where('slug', $slug)->exists()) {
                    continue;
                }

                Color::create([
                    'name_en'    => $english,
                    'name_ar'    => $arabic,
                    'slug'       => $slug,
                    'hex'        => $hex,
                    'sort_order' => ++$position,
                    'is_active'  => true,
                ]);
            }
        }
    }
}
