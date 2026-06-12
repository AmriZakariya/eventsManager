<div class="d-flex align-items-center gap-2">
    <strong>{{ $user->name }} {{ $user->last_name }}</strong>
    {!! $user->profileCompletionBadgeHtml() !!}
    <span class="text-muted">#{{ $user->id }}</span>
</div>
