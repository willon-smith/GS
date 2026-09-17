<?php
/**
 * Template data, loaded once when the database is first created.
 *
 * Everything here is placeholder content. Edit it in the admin panel after
 * the first run (Settings, Products, Grinds, Testimonials); this file is only
 * read when data/cgs.sqlite does not exist yet.
 */

declare(strict_types=1);

$SEED_SETTINGS = [
    // Store identity
    'store_name'        => 'Commodities Good Steward',
    'store_short'       => 'Good Steward',
    'tagline'           => 'Coffee, stewarded from farm to cup.',
    'meta_description'  => 'Single origin coffee roasted in small batches in Bloemfontein. One exceptional roast in three sizes, whole bean or ground the way you brew, delivered anywhere in South Africa.',
    'announcement'      => 'Free delivery on orders over R500  ·  Roasted weekly in small batches  ·  Whole bean or ground to order',

    // Contact
    'email'             => 'hello@goodsteward.co.za',
    'phone'             => '+27 51 000 0000',
    'whatsapp'          => '27510000000',
    'address_line1'     => '12 Roastery Lane, Westdene',
    'address_line2'     => 'Bloemfontein, 9301',
    'hours'             => 'Monday to Friday, 08:00 to 17:00',
    'instagram'         => 'https://instagram.com/',
    'facebook'          => 'https://facebook.com/',

    // Homepage copy
    'hero_eyebrow'      => 'Single origin  ·  Ethiopia, Yirgacheffe',
    'hero_title'        => 'Coffee, stewarded from farm to cup.',
    'hero_subtitle'     => 'One exceptional roast, three bag sizes, ground exactly the way you brew. Roasted in small batches in Bloemfontein and delivered anywhere in South Africa.',
    'featured_group'    => 'stewards-reserve',
    'stewardship_intro' => 'A steward looks after something that is not theirs alone. For us that is the farmer\'s harvest, the land it grew on, and the cup you pour in the morning. Every decision, from what we pay at origin to how we pack a bag, is made with that in mind.',
    'roast_day'         => 'Thursday',
    'dispatch_note'     => 'Orders placed by Wednesday are roasted on Thursday and shipped on Friday.',

    // Delivery
    'shipping_fee_cents'            => '7900',
    'free_shipping_threshold_cents' => '50000',
    'collection_enabled'            => '1',
    'collection_note'               => 'Collect from the roastery in Westdene, Bloemfontein. We will email you when your order is ready, usually within two working days.',
    'delivery_estimate'             => '2 to 4 working days nationwide',

    // Payments
    'payfast_enabled'      => '0',
    'payfast_sandbox'      => '1',
    'payfast_merchant_id'  => '10000100',
    'payfast_merchant_key' => '46f0cd694581a',
    'payfast_passphrase'   => '',
    'eft_enabled'          => '1',
    'eft_bank_name'        => 'First National Bank',
    'eft_account_name'     => 'Commodities Good Steward (Pty) Ltd',
    'eft_account_number'   => '62000000000',
    'eft_branch_code'      => '250655',
    'eft_note'             => 'Use your order number as the payment reference. We roast once payment reflects.',

    // Email
    'mail_enabled'         => '0',
    'mail_from'            => 'orders@goodsteward.co.za',

    // Site behaviour
    'pretty_urls'          => getenv('CGS_PRETTY_URLS') === '1' ? '1' : '0',
    'currency_symbol'      => 'R',
    'about_story'          => "Commodities Good Steward started with a simple question: what would coffee look like if every hand it passed through treated it as something worth looking after?\n\nWe buy one exceptional lot at a time, directly from the washing station, and we pay a price the farmer can plan a season around. We roast it in small batches in Bloemfontein, on a Thursday, and it is at your door within days. Nothing sits in a warehouse. Nothing is blended to hide a bad harvest.\n\nThree bag sizes, one standard. That is the whole range, and we intend to keep it that way until we find another lot that deserves a place beside it.",
];

$COMMON = [
    'group_key'     => 'stewards-reserve',
    'name'          => "Steward's Reserve",
    'description'   => "A washed Yirgacheffe from smallholder farms above 1,900 metres, roasted to a medium profile that keeps the bergamot and honey the region is known for. Bright without being sharp, sweet without sugar, with a black tea finish that lingers.\n\nRoasted in small batches every Thursday and packed with a one-way valve so it arrives at its best.",
    'origin'        => 'Ethiopia',
    'region'        => 'Yirgacheffe, Gedeo Zone',
    'process'       => 'Washed, sun dried on raised beds',
    'altitude'      => '1,900 to 2,100 m',
    'varietal'      => 'Heirloom Ethiopian varieties',
    'roast_level'   => 3,
    'acidity'       => 4,
    'body'          => 3,
    'sweetness'     => 4,
    'tasting_notes' => 'Bergamot, Honey, Stone fruit, Black tea finish',
    'image'         => '',
    'stock'         => -1,
    'active'        => 1,
];

$SEED_PRODUCTS = [
    array_merge($COMMON, [
        'slug'                => 'stewards-reserve-250g',
        'tagline'             => 'For the curious',
        'weight_g'            => 250,
        'size_label'          => '250g',
        'price_cents'         => 16500,
        'compare_price_cents' => 0,
        'cups'                => 15,
        'badge'               => '',
        'accent'              => '#C98B55',
        'sort'                => 1,
    ]),
    array_merge($COMMON, [
        'slug'                => 'stewards-reserve-500g',
        'tagline'             => 'For the daily ritual',
        'weight_g'            => 500,
        'size_label'          => '500g',
        'price_cents'         => 29500,
        'compare_price_cents' => 33000,
        'cups'                => 31,
        'badge'               => 'Most popular',
        'accent'              => '#D8A66A',
        'sort'                => 2,
    ]),
    array_merge($COMMON, [
        'slug'                => 'stewards-reserve-1kg',
        'tagline'             => 'For the household',
        'weight_g'            => 1000,
        'size_label'          => '1kg',
        'price_cents'         => 54000,
        'compare_price_cents' => 66000,
        'cups'                => 62,
        'badge'               => 'Best value',
        'accent'              => '#DCC4A6',
        'sort'                => 3,
    ]),
];

$SEED_GRINDS = [
    ['key' => 'whole-bean', 'name' => 'Whole bean', 'description' => 'Grind at home for the freshest cup. Our recommendation if you own a grinder.', 'brewers' => 'Any brewer', 'icon' => 'bean'],
    ['key' => 'coarse',     'name' => 'Coarse',     'description' => 'Sea salt texture. Long steeps and slow extraction.',                                'brewers' => 'French press, cold brew', 'icon' => 'press'],
    ['key' => 'medium',     'name' => 'Medium',     'description' => 'Table sugar texture. The all rounder for filter brewing.',                          'brewers' => 'Pour-over, filter, drip machine', 'icon' => 'pourover'],
    ['key' => 'fine',       'name' => 'Fine',       'description' => 'Fine sand texture, for pressure and short contact times.',                          'brewers' => 'AeroPress, moka pot', 'icon' => 'moka'],
    ['key' => 'espresso',   'name' => 'Espresso',   'description' => 'Powder fine, dialled for 9 bar machines.',                                          'brewers' => 'Espresso machines', 'icon' => 'espresso'],
];

$SEED_TESTIMONIALS = [
    ['name' => 'Anelle M.',  'location' => 'Pretoria',      'quote' => 'I have bought a lot of "single origin" coffee that tasted like every other bag. This one actually tastes like the notes on the label. The 500g barely lasts us a fortnight.', 'rating' => 5],
    ['name' => 'Thabo K.',   'location' => 'Cape Town',     'quote' => 'Ordered on a Tuesday, roasted Thursday, in my hands Saturday. The roast date is printed on the bag, which is the first thing I look for.', 'rating' => 5],
    ['name' => 'Riaan V.',   'location' => 'Bloemfontein',  'quote' => 'I collect from the roastery every second week. Whole bean, and I grind it myself. Consistent every single time.', 'rating' => 5],
];
