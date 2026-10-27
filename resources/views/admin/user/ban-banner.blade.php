@if($user->exists && $user->isBanned())
    @php($bannedBy = $user->banned_by ? \App\Models\User::find($user->banned_by, ['id', 'name', 'last_name']) : null)
    <div role="status" style="display:flex;gap:12px;align-items:flex-start;background:#fdecec;border:1px solid #f5c2c2;color:#8a1f1f;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:14px;">
        <x-orchid-icon path="bs.slash-circle" width="20" height="20"/>
        <div>
            <strong style="display:block;font-size:15px;margin-bottom:2px;">This user is banned</strong>
            <p style="margin:0;white-space:pre-line;">{{ $user->ban_reason }}</p>
            <small style="color:#a54a4a;">
                Since {{ $user->banned_at->format('M j, Y H:i') }}
                @if($bannedBy) · by {{ trim($bannedBy->name . ' ' . $bannedBy->last_name) }} @endif
                · Hidden from the app with their conversations. Nothing is deleted.
            </small>
        </div>
    </div>
@endif
