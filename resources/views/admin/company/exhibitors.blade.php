@php
    $roleClass = [
        \App\Models\User::APP_ROLE_EXHIBITOR => 'bg-primary',
        \App\Models\User::APP_ROLE_ADMIN     => 'bg-danger',
    ];
@endphp
<div class="bg-white rounded shadow-sm mb-3 p-3">
    @if($exhibitors->isEmpty())
        <div class="text-center text-muted py-5">
            <x-orchid-icon path="bs.people" width="32" height="32"/>
            <p class="mt-2 mb-0">No exhibitors yet. Staff join by entering the company passcode in the app.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small">
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Job title</th>
                        <th>App role</th>
                        <th>Website badge</th>
                        <th class="text-end">Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exhibitors as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($user->avatar)
                                        <img src="{{ $user->adminAvatarUrl() }}" class="rounded-circle" referrerpolicy="no-referrer" style="width:36px;height:36px;object-fit:cover;" alt="">
                                    @else
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:36px;height:36px;">{{ strtoupper(mb_substr($user->name ?? '?', 0, 1)) }}</div>
                                    @endif
                                    <div>
                                        <a href="{{ route('platform.systems.users.view', $user) }}" class="fw-bold text-decoration-none">{{ trim($user->name . ' ' . $user->last_name) }}</a>
                                        <div>{!! $user->profileCompletionBadgeHtml() !!}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $user->email }}</div>
                                @if($user->phone)
                                    <small class="text-muted"><i class="bi bi-phone"></i> {{ $user->phone }}</small>
                                @endif
                            </td>
                            <td>{{ $user->job_title ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $roleClass[$user->role] ?? 'bg-secondary' }}"
                                      @if($user->role !== \App\Models\User::APP_ROLE_EXHIBITOR) title="Linked to this company but not an exhibitor" @endif>
                                    {{ $user->appRoleLabel() }}
                                </span>
                            </td>
                            <td>
                                @if($user->web_badge_code)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Received</span>
                                @else
                                    <span class="badge bg-light text-muted border">Not received</span>
                                @endif
                            </td>
                            <td class="text-end text-muted small">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('platform.systems.users.view', $user) }}" class="btn btn-sm btn-light border">View</a>
                                <a href="{{ route('platform.systems.users.edit', $user) }}" class="btn btn-sm btn-light border">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
