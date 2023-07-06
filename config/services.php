<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => 'http://localhost:8000/auth/google/callback',
    ],

    'paystack' => [
        'url' => env('PAYSTACK_URL', 'https://api.paystack.co/'),
        'key' => env('PAYSTACK_KEY'),
        'secret' => env('PAYSTACK_SECRET_KEY')
    ],

    'qoreid' => [
        'url' => env('QOREID_URL', 'https://api.qoreid.com'),
        'key' => env('QOREID_ID_KEY'),
        'secret' => env('QOREID_SECRET_KEY')
    ],

    'mailjet' => [
        'key' => env('MAILJET_APIKEY'),
        'secret' => env('MAILJET_APISECRET'),
    ],

    'verifyme' => [
        'secret.test' => env('VERIFY_ME_TEST_SECRET_KEY'),
        // 'secret.live' => env()
    ]

];
