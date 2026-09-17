<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform (Backend / Admin) Version
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the backend version, shown in the admin
    | sidebar. Follows Semantic Versioning — MAJOR.MINOR.PATCH:
    |
    |   - PATCH  (1.0.0 -> 1.0.1): bug fixes and small tweaks
    |   - MINOR  (1.0.1 -> 1.1.0): new features, backward compatible
    |   - MAJOR  (1.1.0 -> 2.0.0): breaking changes
    |
    | The mobile app (pubspec.yaml) uses the SAME MAJOR.MINOR.PATCH scheme,
    | plus a "+buildNumber" suffix required by the App Store / Play Store.
    | Keep the MAJOR.MINOR.PATCH aligned between backend and app when you ship
    | a release together, so the version "name" stays unified across both.
    |
    | To bump: edit the value below, commit, and deploy (the deploy's
    | `php artisan config:cache` picks up the new value).
    |
    */

    'number' => '1.0.5',

];
