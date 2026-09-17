@push('head')
    <meta name="robots" content="noindex"/>
    <meta name="google" content="notranslate">
    <link
        href="{{ asset('/favicon.ico') }}"
        sizes="any"
        type="image/svg+xml"
        id="favicon"
        rel="icon"
    >

    <meta name="theme-color" content="#0f172a">
@endpush

@php
    // Resolve the configured app logo (Event Settings) for the sidebar brand.
    $brandLogo = optional(\App\Models\EventSetting::first())->app_logo;
    $brandLogoUrl = empty($brandLogo)
        ? null
        : (\Illuminate\Support\Str::startsWith($brandLogo, 'http') ? $brandLogo : asset($brandLogo));
@endphp

<div class="h2 d-flex align-items-center m-0">
    @auth
        <x-orchid-icon path="bs.house" class="d-inline d-lg-none me-2 text-dark"/>
    @endauth

    <div class="d-flex align-items-center gap-2 {{ auth()->check() ? 'd-none d-lg-flex' : '' }}">
        @if($brandLogoUrl)
            <div class="bg-white rounded-3 d-flex align-items-center justify-content-center shadow-sm p-1 flex-shrink-0"
                 style="width: 40px; height: 40px;">
                <img src="{{ $brandLogoUrl }}"
                     alt="{{ config('app.name') }}"
                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        @else
            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm flex-shrink-0"
                 style="width: 40px; height: 40px; font-size: 1.15rem; font-weight: 800;">
                {{ strtoupper(substr(config('app.name'), 0, 1)) }}
            </div>
        @endif

        <span class="d-flex flex-column lh-1">
            <span class="fw-semibold text-body-emphasis text-truncate"
                  style="font-size: 1.02rem; letter-spacing: -0.2px; max-width: 165px;">
                {{ config('app.name') }}
            </span>
            <small class="text-body-secondary mt-1"
                   style="font-size: 0.68rem; letter-spacing: 0.5px; font-weight: 600;">
                v{{ config('version.number') }}
            </small>
        </span>
    </div>
</div>
