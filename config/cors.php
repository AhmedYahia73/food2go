<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */
    'paths' => ['*', ''], // You might want to specify exact paths for security

    'allowed_methods' => ['*'], // Allows all methods, consider limiting to GET, POST, etc.

    'allowed_origins' => ['*'], // Accepts requests from any origin, restrict this in production

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allows all headers, can be limited

    'exposed_headers' => [], // Specify headers that can be exposed to the browser

    'max_age' => 0, // Sets how long the results of a preflight request can be cached

    'supports_credentials' => true, // Allows cookies and HTTP authentication
];
