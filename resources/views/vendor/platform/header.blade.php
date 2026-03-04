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

<div class="h2 d-flex align-items-center m-0">
    @auth
        <x-orchid-icon path="bs.house" class="d-inline d-lg-none me-2 text-dark"/>
    @endauth

    <div class="d-flex align-items-center {{ auth()->check() ? 'd-none d-lg-flex' : '' }}">
        <div class="bg-dark text-white rounded d-flex align-items-center justify-content-center me-2 shadow-sm"
             style="width: 36px; height: 36px; font-size: 1.1rem; font-weight: 800;">
            {{ substr(config('app.name'), 0, 1) }}
        </div>

        <span class="fw-bolder text-dark" style="letter-spacing: -0.5px; font-size: 1.25rem;">
            {{ config('app.name') }}
        </span>
    </div>
</div>
