@php
    $fullName = trim($user->name . ' ' . $user->last_name);
    $companyName = $user->company?->name ?: $user->company_name;
    $headline = collect([$user->job_title, $companyName])->filter(fn ($v) => filled($v) && $v !== 'N/A')->implode(' · ');
    $isExhibitor = $user->role === \App\Models\User::APP_ROLE_EXHIBITOR;
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value) : null;
    $joined = $date($user->created_at);
    $seen = $date($user->platform_seen_at);
    $initials = strtoupper(mb_substr((string) $user->name, 0, 1) . mb_substr((string) $user->last_name, 0, 1)) ?: 'NA';
    $conversationsUrl = route('platform.conversations.list', ['search' => $user->email]);
@endphp
<style>
.upv { --upv-muted:#596579; --upv-border:#e2e7ef; color:#263449; }
.upv-card { background:#fff; border:1px solid var(--upv-border); border-radius:12px; padding:20px 24px; margin-bottom:20px; }
.upv-head { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.upv-avatar { width:96px; height:96px; border-radius:20px; overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; font-size:30px; font-weight:700; }
.upv-avatar img { width:100%; height:100%; object-fit:cover; }
.upv-id { flex:1; min-width:220px; }
.upv-id h2 { font-size:24px; font-weight:650; margin:0; word-break:break-word; }
.upv-id p { color:var(--upv-muted); margin:4px 0 10px; font-size:15px; }
.upv-badges { display:flex; flex-wrap:wrap; gap:6px; }
.upv-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.upv-badge.exhibitor { background:#eeeaff; color:#5940a4; }
.upv-badge.visitor { background:#fce8f1; color:#9d285b; }
.upv-badge.neutral { background:#f1f4f8; color:#46546b; }
.upv-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
.upv-grid .upv-card { margin-bottom:0; }
.upv-card h3 { font-size:15px; font-weight:650; margin:0 0 14px; display:flex; align-items:center; gap:8px; }
.upv-card h3 svg { color:#7a68d6; }
.upv-dl { display:grid; grid-template-columns:150px minmax(0,1fr); gap:10px 16px; margin:0; font-size:14px; }
.upv-dl dt { color:var(--upv-muted); font-weight:500; }
.upv-dl dd { margin:0; word-break:break-word; }
.upv-empty { color:#a3adbd; }
.upv-bio { white-space:pre-line; unicode-bidi:plaintext; }
.upv-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; }
.upv-stat { display:block; border:1px solid var(--upv-border); border-radius:10px; padding:14px 16px; color:inherit; text-decoration:none; }
a.upv-stat:hover { border-color:#b9aef0; background:#fafbff; color:inherit; }
.upv-stat span { display:block; font-size:12px; color:var(--upv-muted); }
.upv-stat strong { display:block; font-size:22px; font-weight:650; margin-top:4px; }
@media(max-width:900px) { .upv-grid { grid-template-columns:minmax(0,1fr); } }
@media(max-width:520px) { .upv-card { padding:16px; } .upv-dl { grid-template-columns:minmax(0,1fr); gap:2px; } .upv-dl dd { margin-bottom:10px; } }
</style>

@php
    // Renders a value, or a muted dash when it is empty or a social sign-up placeholder.
    $show = function ($value) {
        if (blank($value) || $value === 'N/A') {
            return new \Illuminate\Support\HtmlString('<span class="upv-empty">—</span>');
        }
        return $value;
    };
@endphp

<div class="upv">
    {{-- Header --}}
    <div class="upv-card upv-head">
        <div class="upv-avatar">
            @if($avatar)
                <img src="{{ $avatar }}" alt="{{ $fullName }}" referrerpolicy="no-referrer">
            @else
                {{ $initials }}
            @endif
        </div>
        <div class="upv-id">
            <h2>{{ $fullName ?: 'Unnamed user' }}</h2>
            <p>{{ $headline ?: 'No job title or company' }}</p>
            <div class="upv-badges">
                <span class="upv-badge {{ $isExhibitor ? 'exhibitor' : 'visitor' }}">{{ $user->appRoleLabel() }}</span>
                <span class="badge {{ $user->profileCompletionBadgeClass() }}" title="{{ $user->profileCompletionBadgeTitle() }}">{{ $user->profileCompletionLabel() }}</span>
                @if($user->roles->isNotEmpty())
                    <span class="upv-badge neutral">Admin: {{ $user->adminPanelRolesLabel() }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="upv-grid">
        {{-- Contact --}}
        <section class="upv-card">
            <h3><x-orchid-icon path="bs.person-lines-fill"/> Contact</h3>
            <dl class="upv-dl">
                <dt>Email</dt>
                <dd><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></dd>
                <dt>Phone</dt>
                <dd>
                    @if(filled($user->phone) && $user->phone !== 'N/A')
                        <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                    @else
                        {{ $show(null) }}
                    @endif
                </dd>
                <dt>City</dt>
                <dd>{{ $show($user->city) }}</dd>
                <dt>Country</dt>
                <dd>{{ $show($user->country) }}</dd>
                <dt>LinkedIn</dt>
                <dd>
                    @if(filled($user->linkedin_url))
                        <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener noreferrer">Open profile ↗</a>
                    @else
                        {{ $show(null) }}
                    @endif
                </dd>
            </dl>
        </section>

        {{-- Professional --}}
        <section class="upv-card">
            <h3><x-orchid-icon path="bs.briefcase"/> Professional</h3>
            <dl class="upv-dl">
                <dt>Company</dt>
                <dd>
                    @if($user->company)
                        <a href="{{ route('platform.companies.edit', $user->company->id) }}">{{ $user->company->name }}</a>
                    @else
                        {{ $show($user->company_name) }}
                    @endif
                </dd>
                <dt>Sector</dt>
                <dd>{{ $show($user->company_sector) }}</dd>
                <dt>Job title</dt>
                <dd>{{ $show($user->job_title) }}</dd>
                <dt>Bio</dt>
                <dd class="upv-bio">{{ $show($user->bio) }}</dd>
            </dl>
        </section>

        {{-- Account --}}
        <section class="upv-card">
            <h3><x-orchid-icon path="bs.shield-check"/> Account</h3>
            <dl class="upv-dl">
                <dt>Joined</dt>
                <dd>
                    @if($joined)
                        <span title="{{ $joined->format('M j, Y H:i') }}">{{ $joined->format('M j, Y') }} · {{ $joined->diffForHumans() }}</span>
                    @else
                        {{ $show(null) }}
                    @endif
                </dd>
                <dt>Sign-up method</dt>
                <dd>{{ $user->signupMethodLabel() }}</dd>
                <dt>Created from</dt>
                <dd>{{ $user->created_source ? $user->createdSourceLabel() : 'Unknown' }}</dd>
                <dt>Signed up on</dt>
                <dd>{{ \App\Models\User::PLATFORMS[$user->signup_platform] ?? 'Unknown' }}</dd>
                <dt>Uses the app on</dt>
                <dd>
                    {{ $user->platformLabel() }}
                    @if($user->app_version) <span class="text-muted">· v{{ $user->app_version }}</span> @endif
                </dd>
                <dt>Last seen in app</dt>
                <dd>
                    @if($seen)
                        <span title="{{ $seen->format('M j, Y H:i') }}">{{ $seen->diffForHumans() }}</span>
                    @else
                        {{ $show(null) }}
                    @endif
                </dd>
                <dt>Language</dt>
                <dd>{{ $show($user->locale ? strtoupper($user->locale) : null) }}</dd>
                <dt>Visible in networking</dt>
                <dd>{{ $user->is_visible ? 'Yes' : 'No' }}</dd>
                <dt>Password set</dt>
                <dd>{{ $user->password_is_set ? 'Yes' : 'No (social sign-in only)' }}</dd>
                <dt>Badge code</dt>
                <dd>{{ $show($user->badge_code) }}</dd>
            </dl>
        </section>

        {{-- Activity --}}
        <section class="upv-card">
            <h3><x-orchid-icon path="bs.activity"/> Activity</h3>
            <div class="upv-stats">
                <a class="upv-stat" href="{{ $conversationsUrl }}" title="Open this user's conversations">
                    <span>Conversations</span><strong>{{ number_format($activity['conversations']) }}</strong>
                </a>
                <a class="upv-stat" href="{{ $conversationsUrl }}" title="Open this user's conversations">
                    <span>Messages sent / received</span><strong>{{ number_format($activity['messages_sent']) }} / {{ number_format($activity['messages_received']) }}</strong>
                </a>
                <div class="upv-stat">
                    <span>B2B meetings booked / received</span><strong>{{ number_format($activity['meetings_booked']) }} / {{ number_format($activity['meetings_received']) }}</strong>
                </div>
                <div class="upv-stat">
                    <span>Connections</span><strong>{{ number_format($activity['connections']) }}</strong>
                </div>
                <div class="upv-stat">
                    <span>Pending requests</span><strong>{{ number_format($activity['pending_requests']) }}</strong>
                </div>
            </div>
        </section>
    </div>
</div>
