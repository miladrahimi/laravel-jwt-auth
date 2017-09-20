<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 9/19/17
 * Time: 14:40
 */

return [

    // Secret key to sign tokens
    'key' => env('JWT_KEY', env('APP_KEY', 'your key')),

    // Token time-to-live in seconds (default 30 days)
    'ttl' => 60 * 60 * 24 * 30,

    // Token issuer (your app name)
    'issuer' => env('JWT_ISSUER', env('APP_NAME', 'your app')),

    // Token audience (your api customer)
    'audience' => env('JWT_AUDIENCE', ''),

];