{{-- Read-only profile picture preview on the user edit screen --}}
@if(!empty($avatar_url))
    <div class="mb-3 d-flex align-items-center gap-3">
        <img src="{{ $avatar_url }}"
             alt="Profile picture"
             style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;box-shadow:0 2px 8px rgba(15,23,42,0.08);">
        <div>
            <div class="fw-semibold">Current profile picture</div>
            <a href="{{ $avatar_url }}" target="_blank" class="small text-muted">Open full size</a>
        </div>
    </div>
@endif
