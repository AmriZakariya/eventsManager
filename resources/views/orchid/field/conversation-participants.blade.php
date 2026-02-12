<div class="d-flex align-items-center justify-content-between w-100">
    <!-- Participant 1 -->
    <div class="d-flex align-items-center" style="min-width: 200px;">
        @if($p1)
            <div class="position-relative mr-2">
                <img src="{{ $p1->avatar_url ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($p1->email))) . '?d=mp' }}"
                     class="rounded-circle border"
                     style="width: 40px; height: 40px; object-fit: cover;">
                <span class="position-absolute bottom-0 right-0 bg-success rounded-circle"
                      style="width: 10px; height: 10px; border: 2px solid white;"></span>
            </div>
            <div>
                <div class="d-flex align-items-center">
                    <strong class="mr-2">{{ $p1->name }} {{ $p1->last_name }}</strong>
                    @if($p1->roles->contains('slug', 'exhibitor'))
                        <span class="badge bg-primary-light text-primary px-2 py-1" style="font-size: 0.7rem;">
                            <i class="bs.building mr-1"></i> Exhibitor
                        </span>
                    @else
                        <span class="badge bg-success-light text-success px-2 py-1" style="font-size: 0.7rem;">
                            <i class="bs.person mr-1"></i> Visitor
                        </span>
                    @endif
                </div>
                <small class="text-muted d-block text-truncate" style="max-width: 180px;">
                    <i class="bs.envelope-fill me-1" style="font-size: 0.7rem;"></i>
                    {{ $p1->company->name ?? $p1->email }}
                </small>
            </div>
        @else
            <div class="text-muted">
                <i class="bs.person-x"></i> Deleted User
            </div>
        @endif
    </div>

    <!-- VS Divider -->
    <div class="mx-3 text-muted">
        <span class="bg-light rounded-circle d-flex align-items-center justify-content-center"
              style="width: 32px; height: 32px;">
            <i class="bs.arrow-right-short fs-5"></i>
        </span>
    </div>

    <!-- Participant 2 -->
    <div class="d-flex align-items-center" style="min-width: 200px;">
        @if($p2)
            <div class="position-relative mr-2">
                <img src="{{ $p2->avatar_url ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($p2->email))) . '?d=mp' }}"
                     class="rounded-circle border"
                     style="width: 40px; height: 40px; object-fit: cover;">
                <span class="position-absolute bottom-0 right-0 bg-success rounded-circle"
                      style="width: 10px; height: 10px; border: 2px solid white;"></span>
            </div>
            <div>
                <div class="d-flex align-items-center">
                    <strong class="mr-2">{{ $p2->name }} {{ $p2->last_name }}</strong>
                    @if($p2->roles->contains('slug', 'exhibitor'))
                        <span class="badge bg-primary-light text-primary px-2 py-1" style="font-size: 0.7rem;">
                            <i class="bs.building mr-1"></i> Exhibitor
                        </span>
                    @else
                        <span class="badge bg-success-light text-success px-2 py-1" style="font-size: 0.7rem;">
                            <i class="bs.person mr-1"></i> Visitor
                        </span>
                    @endif
                </div>
                <small class="text-muted d-block text-truncate" style="max-width: 180px;">
                    <i class="bs.envelope-fill me-1" style="font-size: 0.7rem;"></i>
                    {{ $p2->company->name ?? $p2->email }}
                </small>
            </div>
        @else
            <div class="text-muted">
                <i class="bs.person-x"></i> Deleted User
            </div>
        @endif
    </div>
</div>
