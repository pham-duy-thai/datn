<?php

return [
    'methods' => ['cash', 'vnpay', 'momo'],

    'deposit_percent' => (float) env('PAYMENT_DEPOSIT_PERCENT', 30),

    'vnpay' => [
        'payment_url' => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'return_url' => env('VNPAY_RETURN_URL'),
        'ipn_url' => env('VNPAY_IPN_URL'),
    ],

    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'return_url' => env('MOMO_RETURN_URL'),
        'ipn_url' => env('MOMO_IPN_URL'),
    ],
];
