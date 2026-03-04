<div class="text-center user-select-none mt-4 pb-3">
    <p class="small text-muted mb-1" style="font-size: 0.85rem;">
        &copy; {{ date('Y') }} <strong>{{ config('app.name') }}</strong>. {{ __('All rights reserved.') }}
    </p>

    @auth
        <p class="small text-muted mb-0" style="font-size: 0.75rem;">
            <a href="{{ url('/') }}" target="_blank" class="text-decoration-none text-muted" style="transition: color 0.2s ease;">
                {{ __('Return to Website') }} &rarr;
            </a>
        </p>
    @endauth
</div>

<style>
    .text-muted a:hover {
        color: #0f172a !important; /* Darken on hover */
    }
</style>
