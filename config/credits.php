<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credit System Configuration
    |--------------------------------------------------------------------------
    | All values here are overridable via the admin panel once built.
    | These serve as the initial defaults.
    */

    'signup_bonus' => (int) env('CREDITS_SIGNUP_BONUS', 3),

    'costs' => [
        'lab_interpretation' => (int) env('CREDITS_LAB_INTERPRETATION', 2),
        'pdf_interpretation' => (int) env('CREDITS_PDF_INTERPRETATION', 3),
        'symptom_check' => (int) env('CREDITS_SYMPTOM_CHECK', 1),
    ],

    'daily_api_limit' => (int) env('DEEPSEEK_DAILY_LIMIT_PER_USER', 20),
];
