<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credit Costs for Features
    |--------------------------------------------------------------------------
    */
    'costs' => [
        'lab_interpretation' => 2,     // Per panel interpretation
        'single_test_interpretation' => 1, // Individual test (new lower-cost option)
        'pdf_interpretation' => 3,     // PDF upload interpretation
        'symptom_check' => 1,          // Symptom checker AI
        'trend_summary_share' => 0,    // Free — sharing trend with doctor
    ],

    /*
    |--------------------------------------------------------------------------
    | Signup / Promotional Credits
    |--------------------------------------------------------------------------
    */
    'signup_bonus' => 2,              // Free credits on registration
    'first_interpretation_free' => true, // First interpretation is free for new users

    /*
    |--------------------------------------------------------------------------
    | Affordable Package Structures
    | Prices in kobo (₦1 = 100 kobo)
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'single_test' => [
            'name' => 'Single Test',
            'credits' => 1,
            'price_kobo' => 20000,      // ₦200
            'sort_order' => 1,
        ],
        'quick_check' => [
            'name' => 'Quick Check (3)',
            'credits' => 3,
            'price_kobo' => 50000,      // ₦500
            'sort_order' => 2,
        ],
        'value_pack' => [
            'name' => 'Value Pack (10)',
            'credits' => 10,
            'price_kobo' => 150000,     // ₦1,500
            'sort_order' => 3,
        ],
        'family_pack' => [
            'name' => 'Family Pack (25)',
            'credits' => 25,
            'price_kobo' => 300000,     // ₦3,000
            'sort_order' => 4,
        ],
        'health_partner' => [
            'name' => 'Health Partner (50)',
            'credits' => 50,
            'price_kobo' => 500000,     // ₦5,000
            'sort_order' => 5,
        ],
    ],
];