{!! $styles !!}
<style>
.ch-history { --ch-muted:#596579; color:#263449; }
.ch-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
.ch-metric,.ch-toolbar,.ch-list { background:#fff; border:1px solid #e2e7ef; border-radius:12px; }
.ch-metric { padding:18px 20px; }
.ch-metric span { display:block; color:var(--ch-muted); font-size:13px; }
.ch-metric strong { display:block; font-size:26px; margin-top:6px; font-weight:650; }
.ch-toolbar { padding:20px; margin-bottom:20px; }
.ch-toolbar h2 { font-size:17px; margin:0 0 6px; }
.ch-toolbar p { color:var(--ch-muted); font-size:13px; margin-bottom:18px; }
.ch-controls { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; }
.ch-control { flex:1; min-width:150px; }
.ch-control-search { flex:2; }
.ch-control label { display:block; font-size:12px; font-weight:600; margin-bottom:6px; }
.ch-control input,.ch-control select { width:100%; min-height:40px; border:1px solid #cdd5e1; border-radius:7px; padding:8px 10px; background:#fff; color:#263449; }
.ch-controls .btn { min-height:40px; }
.ch-results { display:flex; justify-content:space-between; gap:12px; align-items:center; margin:0 0 12px; font-size:13px; color:var(--ch-muted); }
.ch-results h2 { font-size:16px; color:#263449; margin:0; }
.ch-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.4fr) minmax(170px,.75fr); gap:24px; align-items:center; }
.ch-header { padding:14px 20px; background:#f8fafc; border-radius:12px 12px 0 0; font-size:12px; font-weight:600; color:var(--ch-muted); }
.ch-row { padding:20px; border-top:1px solid #e8ecf2; }
.ch-row:hover { background:#fafbff; }
.ch-cell { min-width:0; }
.ch-mobile-label { display:none; }
.ch-history .uc-avatar { width:42px; height:42px; border-radius:10px; box-shadow:none; }
.ch-history .uc-name { white-space:normal; font-size:14px; line-height:1.4; }
.ch-history .uc-meta { color:var(--ch-muted); font-size:12px; }
.ch-history .uc-badge { font-size:10px; letter-spacing:0; text-transform:none; font-weight:600; }
.ch-history .uc-badge.exhibitor { background:#eeeaff; color:#5940a4; }
.ch-history .uc-badge.visitor { background:#fce8f1; color:#9d285b; }
.ch-history .cs { align-items:flex-start; gap:8px; }
.ch-history .cs-val { font-size:14px; }
.ch-history .cs-dur { background:none; border-radius:0; padding:0; font-size:12px; color:var(--ch-muted); font-weight:400; }
.ch-history .cs-btn { background:none; box-shadow:none; padding:4px 0; color:#5444b4; font-size:13px; }
.ch-history .cs-btn:hover { transform:none; box-shadow:none; text-decoration:underline; }
.ch-history .lm-head { flex-wrap:wrap; color:var(--ch-muted); font-size:11px; gap:5px; }
.ch-history .lm-dir { max-width:100%; background:none; padding:0; font-size:12px; }
.ch-history .lm-body { font-size:14px; line-height:1.5; margin-top:4px; unicode-bidi:plaintext; }
.ch-history a:focus-visible,.ch-history input:focus-visible,.ch-history select:focus-visible,.ch-history button:focus-visible { outline:3px solid #9a8fe8; outline-offset:3px; }
.ch-empty { text-align:center; padding:48px 20px; }
.ch-empty h3 { font-size:18px; }
.ch-empty p { color:var(--ch-muted); }
.ch-pagination { margin-top:20px; }
@media(max-width:1100px) { .ch-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; } .ch-header { display:none; } .ch-mobile-label { display:block; font-size:11px; font-weight:600; color:var(--ch-muted); margin-bottom:6px; } }
@media(max-width:600px) { .ch-summary { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; } .ch-metric { padding:14px; } .ch-grid { grid-template-columns:minmax(0,1fr); } .ch-control { min-width:100%; } .ch-results { flex-wrap:wrap; } .ch-row { padding:16px; } }
</style>
<div class="ch-history">
    <div class="ch-summary" aria-label="Platform totals across all conversations">
        @foreach(['total_convos' => 'Total conversations', 'total_messages' => 'Total messages', 'active_today' => 'Active today', 'avg_messages' => 'Messages per conversation'] as $key => $label)
            <div class="ch-metric"><span>{{ $label }}</span><strong>{{ $metrics[$key] }}</strong></div>
        @endforeach
    </div>
    <section class="ch-toolbar" aria-labelledby="ch-find">
        <h2 id="ch-find">Find a conversation</h2>
        <p>Filter by either participant and the latest message date. Message counts and spans cover the full conversation.</p>
        <div class="ch-controls">
            <div class="ch-control ch-control-search">
                <label for="ch-search">Participant name or email</label>
                <input id="ch-search" type="search" name="search" form="filters" value="{{ request('search') }}" placeholder="Search participants…">
            </div>
            <div class="ch-control">
                <label for="ch-role">Participant role</label>
                <select id="ch-role" name="role" form="filters">
                    @foreach(['all'=>'All roles','exhibitor'=>'Includes an exhibitor','visitor'=>'Includes a visitor'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('role','all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ch-control">
                <label for="ch-period">Latest activity</label>
                <select id="ch-period" name="activity" form="filters">
                    @foreach(['all'=>'All time','today'=>'Today','week'=>'Last 7 days','month'=>'Last 30 days','quarter'=>'Last 3 months','year'=>'Last year'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('activity','all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ch-control">
                <label for="ch-sort">Sort by</label>
                <select id="ch-sort" name="sort" form="filters">
                    @foreach(['last_message_at'=>'Latest activity','total_messages'=>'Most messages','first_message_at'=>'Recently started'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('sort','last_message_at') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" form="filters" class="btn btn-primary">Apply filters</button>
            <a href="{{ route('platform.conversations.list') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </section>
    <div class="ch-results">
        <h2>Conversations <span class="text-muted">({{ number_format($conversations->total()) }})</span></h2>
        <span>@if($conversations->total()) Showing {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }} · @endif Platform totals above include all conversations</span>
    </div>
    <div class="ch-list">
        @if($conversations->isNotEmpty())
            <div class="ch-grid ch-header" aria-hidden="true"><span>Initiator · sent the first message</span><span>Recipient</span><span>Last message</span><span>Activity</span></div>
            @foreach($conversations as $conversation)
                <article class="ch-grid ch-row" aria-label="Conversation between {{ $conversation->p1?->name ?? 'Deleted user' }} and {{ $conversation->p2?->name ?? 'Deleted user' }}">
                    <div class="ch-cell"><span class="ch-mobile-label">Initiator · sent the first message</span>{!! $conversation->initiator_card !!}</div>
                    <div class="ch-cell"><span class="ch-mobile-label">Recipient</span>{!! $conversation->recipient_card !!}</div>
                    <div class="ch-cell"><span class="ch-mobile-label">Last message</span>{!! $conversation->preview_card !!}</div>
                    <div class="ch-cell"><span class="ch-mobile-label">Activity</span>{!! $conversation->activity_card !!}</div>
                </article>
            @endforeach
        @else
            <div class="ch-empty"><h3>No conversations found</h3><p>Try another name, a different role, or a wider activity period.</p><a href="{{ route('platform.conversations.list') }}" class="btn btn-outline-primary">Clear filters</a></div>
        @endif
    </div>
    <div class="ch-pagination">{{ $conversations->onEachSide(1)->links() }}</div>
</div>
