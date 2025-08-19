<?php

declare(strict_types=1);


use Illuminate\Support\Str;

$env = env('APP_ENV', 'local');
$apex = env('APP_DOMAIN', 'brachy.io');

// Build environment-aware domain configuration
if (in_array($env, ['production', 'staging'])) {
    $sessionDomain = ".$apex"; // .brachy.io
    $stateful = [
        "app.$apex",
        "admin.$apex",
        "partners.$apex",
    ];
    $appUrl = 'https://app.' . $apex;
} else {
    // local / testing: use *.app.brachy.io :8000
    $sessionDomain = '.app.' . $apex; // .app.brachy.io
    $stateful = [
        "app.app.$apex:8000",
        "admin.app.$apex:8000",
        "partners.app.$apex:8000",
        "app.$apex:8000",
        '127.0.0.1:8000',
        'localhost:8000',
    ];
    $appUrl = 'http://app.app.' . $apex . ':8000';
}

return [
    // Computed values used across the app
    'app_url' => $appUrl,
    'session_domain' => $sessionDomain,
    'stateful_domains' => $stateful,
    // Base apex domain used in production (e.g., branchy.io)
    'base' => $apex,

    // Subdomain labels
    'subdomains' => [
        'app' => env('APP_SUBDOMAIN_APP', 'app'),
        'admin' => env('APP_SUBDOMAIN_ADMIN', 'admin'),
        'partners' => env('APP_SUBDOMAIN_PARTNERS', 'partners'),
    ],

    // Enable domain-constrained routing groups
    'enabled' => (bool) env('ENABLE_SUBDOMAIN_ROUTING', false),
];

