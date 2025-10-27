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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'delphi' => [
        'url' => env('DELPHI_EMISSOR_URL', 'http://127.0.0.1:18080'),
        'timeout' => env('DELPHI_EMISSOR_TIMEOUT', 30),
        // Esquema de autenticação do emissor: bearer | x-token | query | none
        'auth' => env('DELPHI_EMISSOR_AUTH', 'x-token'),
        // Token padrão (pode ser sobrescrito via Settings no admin)
        'token' => env('DELPHI_EMISSOR_TOKEN', 'qfiscal_default_token_2025'),
    ],

];
