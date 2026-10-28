<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Low-Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Products with stock less than or equal to this threshold will be flagged
    | as low-stock in the retail counter dashboard and low-stock API.
    |
    */
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Currency Symbol & ISO Code
    |--------------------------------------------------------------------------
    */
    'currency_symbol' => '₹',
    'currency_code' => 'INR',
];
