<?php

return [

    'api_key' => env('ASIN_API_KEY'),

    'api_url' => env('ASIN_API_URL', 'https://api.asindataapi.com'),

    'api_language' => env('ASIN_API_LANGUAGE', 'en_US'),

    'timeout' => env('ASIN_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Amazon Domain
    |--------------------------------------------------------------------------
    |
    |   The Amazon Domain you want to get the information from
    |   https://docs.trajectdata.com/asindataapi/product-data-api/reference/amazon-domains
    |
    */
    'amazon_domain' => env('ASIN_AMAZON_DOMAIN', 'amazon.de'),

    /*
    |--------------------------------------------------------------------------
    | API Request Output Format
    |--------------------------------------------------------------------------
    |
    |   The fields you need
    |   https://docs.trajectdata.com/asindataapi/product-data-api/parameters/common
    |
    */
    'include_fields' => 'request_info,product.title,product.asin,product.main_image.link,product.buybox_winner,product.link',

    /*
    |--------------------------------------------------------------------------
    | API Request Output Format
    |--------------------------------------------------------------------------
    |
    |   The format of the output that the request to the api will be returned
    |
    */
    'output_format' => 'json',



];
