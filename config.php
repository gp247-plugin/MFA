<?php

return [
    // Guard used when the mfa.verify middleware is attached without a guard parameter
    'default_guard' => 'customer',

    // Guards configuration — only guards that exist in GP247 (core, shop, MultiVendor).
    // Site owners edit enabled/forced/tunables from the admin screen (stored in admin_config);
    // this file only holds package defaults and is replaced on plugin update.
    'guards' => [
        'customer' => [
            'enabled' => 0,
            'forced' => 0,
            'model' => 'GP247\Shop\Models\ShopCustomer',
            'redirect_after_verify' => 'front.home',
            'redirect_after_login' => 'mfa.verify.show',
            'redirect_need_login' => 'customer.login',
            'qr_code_size' => 200,
            'recovery_codes_count' => 8,
            'window' => 1, // Time window in 30-second increments
            'trust_device_days' => 30, // "Trust this device" lifetime; 0 turns the option off
        ],
        'admin' => [
            'enabled' => 1,
            'forced' => 0,
            'model' => 'GP247\Core\Models\AdminUser',
            'redirect_after_verify' => 'admin.home',
            'redirect_after_login' => 'mfa.verify.show',
            'redirect_need_login' => 'admin.login',
            'qr_code_size' => 200,
            'recovery_codes_count' => 8,
            'window' => 1,
            'trust_device_days' => 30,
        ],
        'vendor' => [
            'enabled' => 0,
            'forced' => 0,
            'model' => 'App\GP247\Plugins\MultiVendor\Models\VendorUser',
            'redirect_after_verify' => 'vendor_admin.home',
            'redirect_after_login' => 'mfa.verify.show',
            'redirect_need_login' => 'vendor.login',
            'qr_code_size' => 200,
            'recovery_codes_count' => 8,
            'window' => 1,
            'trust_device_days' => 30,
        ],
    ],

    // Application name to display in authenticator apps
    'app_name' => env('APP_NAME', 'GP247'),

    // Enable/disable recovery codes
    'enable_recovery_codes' => true,

    // Session key to store MFA verification status ([guard => user id])
    'session_key' => 'mfa_verified',

    // QR Code configuration
    'qr_code' => [
        'size' => 200,
        'margin' => 0,
        'error_correction' => 'high', // low, medium, quartile, high
    ],
];
