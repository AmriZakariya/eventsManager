{{-- resources/views/vendor/orchid/partials/conversation-preview.blade.php --}}
<div class="d-flex align-items-center gap-4 conversation-preview">
    <div class="flex-grow-1" style="min-width: 0;">
        <div class="d-flex align-items-center gap-3">
            {{-- Participant 1 --}}
            <div class="flex-grow-1" style="min-width: 0;">
                @include('orchid::partials.user-badge', ['user' => $participant1])
            </div>

            {{-- Connection indicator --}}
            <div class="flex-shrink-0 d-flex flex-column align-items-center px-2">
                <div class="bg-light rounded-circle p-2 d-flex align-items-center justify-content-center"
                     style="width: 32px; height: 32px;">
                    <i class="bs.arrow-right-short fs-5 text-muted"></i>
                </div>
                @if($isActive)
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 mt-1 fs-xs">
                        Active
                    </span>
                @endif
            </div>

            {{-- Participant 2 --}}
            <div class="flex-grow-1" style="min-width: 0;">
                @include('orchid::partials.user-badge', ['user' => $participant2])
            </div>
        </div>
    </div>
</div>

<style>
    .conversation-preview .hover-underline:hover {
        text-decoration: underline !important;
    }
    .conversation-participant .avatar {
        transition: transform 0.2s ease;
    }
    .conversation-participant:hover .avatar {
        transform: scale(1.05);
    }
    .min-w-0 {
        min-width: 0;
    }
    .fs-xs {
        font-size: 0.75rem;
    }
    .fs-sm {
        font-size: 0.875rem;
    }
    .object-fit-cover {
        object-fit: cover;
    }
</style>
