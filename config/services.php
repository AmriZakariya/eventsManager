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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    // LinkedIn "Sign In with OpenID Connect". The app only sends the one-time
    // code; the secret stays on the server. The redirect must match the one
    // registered on the LinkedIn app and the one the app opens.
    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', 'https://www.linkedin.com/developers/tools/oauth/redirect'),
    ],

    'apple' => [
        // Native "Sign in with Apple" flow: the identity token's audience (aud)
        // is the iOS app's bundle id. Used to verify tokens in AuthController.
        'client_id' => env('APPLE_CLIENT_ID', 'com.hygiecleanexpo.app'),
    ],

    'wordpress_sync' => [
        'url' => env('WORDPRESS_SYNC_URL', 'https://hygiecleanexpo.com/wp-json/hc-sync/v1/users'),
        'outbound_token' => env('WORDPRESS_SYNC_OUTBOUND_TOKEN'),
        'inbound_token' => env('WORDPRESS_SYNC_INBOUND_TOKEN'),
    ],

];
