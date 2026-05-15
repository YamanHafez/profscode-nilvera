<?php

return [
    'api_key' => env('NILVERA_API_KEY', ''),
    'base_url' => env('NILVERA_BASE_URL', 'https://api.nilvera.com'),
    'username' => env('NILVERA_USERNAME', ''),
    'password' => env('NILVERA_PASSWORD', ''),
    'company' => [
        'name' => env('NILVERA_COMPANY_NAME', ''),
        'tax_number' => env('NILVERA_COMPANY_TAX_NUMBER', ''),
        'tax_office' => env('NILVERA_COMPANY_TAX_OFFICE', ''),
        'address' => env('NILVERA_COMPANY_ADDRESS', ''),
        'district' => env('NILVERA_COMPANY_DISTRICT', ''),
        'city' => env('NILVERA_COMPANY_CITY', ''),
        'country' => env('NILVERA_COMPANY_COUNTRY', 'Türkiye'),
        'postal_code' => env('NILVERA_COMPANY_POSTAL_CODE', ''),
        'phone' => env('NILVERA_COMPANY_PHONE', ''),
        'mail' => env('NILVERA_COMPANY_MAIL', ''),
        'website' => env('NILVERA_COMPANY_WEBSITE', ''),
    ],
];
