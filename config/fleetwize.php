<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Seeded by database/seeders/AdminSeeder.php. There is no public
    | self-registration on the web dashboard — this is the only account
    | provisioned outside of an admin/agent/company-driven flow.
    |
    */

    'admin' => [
        'first_name' => env('ADMIN_FIRST_NAME', 'Fleetwize'),
        'last_name' => env('ADMIN_LAST_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@fleetwize.io'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy
    |--------------------------------------------------------------------------
    |
    | Each company gets a subdomain of this base domain, e.g. a company
    | with slug "acme" is served at "acme.{tenant_domain}". See
    | routes/company.php and App\Http\Middleware\EnsureCompanyTenant.
    |
    */

    'tenant_domain' => env('TENANT_DOMAIN', 'fleetwize.test'),

    /*
    |--------------------------------------------------------------------------
    | Tracker Middleware (Traccar)
    |--------------------------------------------------------------------------
    |
    | Trackers report to a self-hosted Traccar instance, which forwards each
    | decoded position to our webhook (see routes/webhooks.php). `webhook_secret`
    | must match Traccar's `forward.header` config (X-Webhook-Secret: <value>).
    | `base_url`/`username`/`password` are for App\Services\Tracking\TraccarService's
    | on-demand REST pulls (admin refresh/backfill), authenticated via Traccar's
    | session-cookie login since no static API token is configured on the server.
    |
    */

    'traccar' => [
        'base_url' => env('TRACCAR_BASE_URL', 'http://localhost:8082'),
        'username' => env('TRACCAR_USERNAME'),
        'password' => env('TRACCAR_PASSWORD'),
        'webhook_secret' => env('TRACCAR_WEBHOOK_SECRET'),

        // Devices auto-registered via Traccar's database.registerUnknown have
        // no owner, and are invisible on the map/API to anyone until linked to
        // a user (see TraccarService::linkDeviceToUser). This is the Traccar
        // user ID new devices get linked to — the one admin account, today.
        'owner_user_id' => env('TRACCAR_OWNER_USER_ID', 1),
    ],

];
