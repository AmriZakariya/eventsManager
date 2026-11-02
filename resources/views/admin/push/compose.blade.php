@php
    use App\Models\AdminBroadcast;

    $audience = old('broadcast.audience', AdminBroadcast::AUDIENCE_ALL);
    $languages = ['en' => 'English', 'fr' => 'Français', 'ar' => 'العربية'];
    $audienceInfo = [
        AdminBroadcast::AUDIENCE_ALL        => ['icon' => 'bs.people',      'hint' => 'Everyone with an account'],
        AdminBroadcast::AUDIENCE_VISITORS   => ['icon' => 'bs.person',      'hint' => 'Visitor accounts only'],
        AdminBroadcast::AUDIENCE_EXHIBITORS => ['icon' => 'bs.shop',        'hint' => 'Exhibitor accounts only'],
        AdminBroadcast::AUDIENCE_SPECIFIC   => ['icon' => 'bs.person-check','hint' => 'Pick people by name or email'],
    ];

    $userPicker = \Orchid\Screen\Fields\Relation::make('broadcast.user_ids')
        ->fromModel(\App\Models\User::class, 'name')
        ->displayAppend('adminPickerLabel')
        ->searchColumns('last_name', 'email')
        ->applyScope('notBanned')
        ->multiple()
        ->chunk(20)
        ->placeholder('Type a name or email…')
        ->value(old('broadcast.user_ids', []));

    $sendButton = \Orchid\Screen\Actions\Button::make('Send notification')
        ->icon('bs.send')
        ->method('sendBroadcast')
        ->class('btn btn-primary')
        ->confirm('Send this notification?');

    $testButton = \Orchid\Screen\Actions\Button::make('Send test to me')
        ->icon('bs.phone')
        ->method('sendTest')
        ->class('btn btn-outline-secondary');
@endphp

<style>
.pc { --pc-muted:#5d6a7e; --pc-border:#e2e7ef; --pc-accent:#5444b4; color:#263449; }
.pc-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:24px; align-items:start; }
.pc-card { background:#fff; border:1px solid var(--pc-border); border-radius:14px; padding:22px 24px; margin-bottom:20px; }
.pc-card h3 { font-size:16px; font-weight:650; margin:0 0 4px; }
.pc-card > p { color:var(--pc-muted); font-size:13px; margin:0 0 16px; }
.pc-step { display:inline-flex; width:22px; height:22px; border-radius:50%; background:#eeeaff; color:var(--pc-accent); font-size:12px; font-weight:700; align-items:center; justify-content:center; margin-right:8px; }

/* Audience */
.pc-aud { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
.pc-aud input { position:absolute; opacity:0; pointer-events:none; }
.pc-aud label { position:relative; display:block; border:1.5px solid var(--pc-border); border-radius:12px; padding:14px; cursor:pointer; height:100%; transition:border-color .15s, background .15s; }
.pc-aud label:hover { border-color:#c4bbf2; }
.pc-aud input:checked + label { border-color:var(--pc-accent); background:#f7f5ff; box-shadow:0 0 0 3px rgba(84,68,180,.12); }
.pc-aud input:focus-visible + label { outline:3px solid #9a8fe8; outline-offset:2px; }
.pc-aud .pc-aud-name { display:flex; align-items:center; gap:8px; font-weight:600; font-size:14px; }
.pc-aud .pc-aud-name svg { color:var(--pc-accent); }
.pc-aud .pc-aud-count { display:block; font-size:22px; font-weight:700; margin-top:8px; }
.pc-aud small { display:block; color:var(--pc-muted); font-size:12px; margin-top:2px; }
.pc-picker { margin-top:14px; }
.pc-picker[hidden] { display:none; }

/* Languages */
.pc-langs { display:flex; gap:6px; border-bottom:1px solid var(--pc-border); margin-bottom:16px; flex-wrap:wrap; }
.pc-langs button { border:0; background:none; padding:9px 14px; font-size:14px; font-weight:500; color:var(--pc-muted); border-bottom:2px solid transparent; margin-bottom:-1px; display:inline-flex; align-items:center; gap:7px; }
.pc-langs button[aria-selected="true"] { color:var(--pc-accent); border-bottom-color:var(--pc-accent); font-weight:600; }
.pc-langs .pc-dot { width:8px; height:8px; border-radius:50%; background:#d5dbe5; }
.pc-langs .pc-dot.is-filled { background:#2fa36b; }
.pc-langs .pc-dot.is-partial { background:#e6a23c; }
.pc-langs .pc-opt { font-size:11px; color:#97a1b2; font-weight:400; }
.pc-field { margin-bottom:14px; }
.pc-field label { display:flex; justify-content:space-between; font-size:13px; font-weight:600; margin-bottom:6px; }
.pc-field label span { font-weight:400; color:var(--pc-muted); font-variant-numeric:tabular-nums; }
.pc-field label span.is-over { color:#c0392b; font-weight:600; }
.pc-field input, .pc-field textarea { width:100%; border:1px solid #cdd5e1; border-radius:9px; padding:10px 12px; font-size:15px; color:#263449; background:#fff; }
.pc-field textarea { min-height:110px; resize:vertical; }
.pc-field input:focus, .pc-field textarea:focus { outline:none; border-color:var(--pc-accent); box-shadow:0 0 0 3px rgba(84,68,180,.15); }
.pc-tip { font-size:12px; color:var(--pc-muted); margin:0; }

/* Footer */
.pc-footer { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.pc-summary { font-size:14px; }
.pc-summary strong { font-size:16px; }
.pc-summary small { display:block; color:var(--pc-muted); }
.pc-actions { display:flex; gap:10px; flex-wrap:wrap; }
.pc-actions .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 18px; border-radius:9px; font-weight:600; }

/* Preview */
.pc-preview { position:sticky; top:16px; }
.pc-phone { background:linear-gradient(160deg,#1f2a44,#3a2f6b); border-radius:34px; padding:18px 14px 26px; box-shadow:0 18px 40px rgba(31,42,68,.25); }
.pc-phone-time { color:#fff; text-align:center; font-size:44px; font-weight:300; margin:18px 0 2px; letter-spacing:1px; }
.pc-phone-date { color:rgba(255,255,255,.75); text-align:center; font-size:13px; margin-bottom:22px; }
.pc-notif { background:rgba(255,255,255,.92); border-radius:16px; padding:11px 13px; backdrop-filter:blur(6px); }
.pc-notif-head { display:flex; align-items:center; gap:7px; font-size:11px; color:#5d6a7e; text-transform:uppercase; letter-spacing:.3px; margin-bottom:4px; }
.pc-notif-icon { width:18px; height:18px; border-radius:5px; background:linear-gradient(135deg,#667eea,#764ba2); }
.pc-notif-head time { margin-left:auto; text-transform:none; }
.pc-notif-title { font-weight:650; font-size:14px; color:#111; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; unicode-bidi:plaintext; }
.pc-notif-body { font-size:13.5px; color:#222; line-height:1.35; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden; unicode-bidi:plaintext; white-space:pre-line; }
.pc-notif-empty { color:#9aa3b2; font-style:italic; }
.pc-preview-note { font-size:12px; color:var(--pc-muted); margin-top:10px; text-align:center; }

@media (max-width:1100px) { .pc-layout { grid-template-columns:minmax(0,1fr); } .pc-preview { position:static; max-width:360px; } }
@media (max-width:700px) { .pc-aud { grid-template-columns:repeat(2,minmax(0,1fr)); } .pc-card { padding:16px; } .pc-actions { width:100%; } .pc-actions .btn { flex:1; justify-content:center; } }
</style>

<div class="pc" id="push-compose"
     data-counts='@json($audiences)'>
    <div class="pc-layout">
        <div>
            {{-- 1. Audience --}}
            <section class="pc-card" aria-labelledby="pc-aud-title">
                <h3 id="pc-aud-title"><span class="pc-step">1</span>Who should receive it?</h3>
                <p>Banned accounts never receive notifications.</p>

                <div class="pc-aud" role="radiogroup" aria-labelledby="pc-aud-title">
                    @foreach(AdminBroadcast::AUDIENCES as $value => $label)
                        <div>
                            <input type="radio" name="broadcast[audience]" id="pc-aud-{{ $value }}" value="{{ $value }}" @checked($audience === $value)>
                            <label for="pc-aud-{{ $value }}">
                                <span class="pc-aud-name"><x-orchid-icon path="{{ $audienceInfo[$value]['icon'] }}"/> {{ $label }}</span>
                                @if(isset($audiences[$value]))
                                    <span class="pc-aud-count">{{ number_format($audiences[$value]['total']) }}</span>
                                    <small>{{ number_format($audiences[$value]['reachable']) }} with notifications on</small>
                                @else
                                    <span class="pc-aud-count" data-selected-count>0</span>
                                    <small>{{ $audienceInfo[$value]['hint'] }}</small>
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="pc-picker" data-picker @if($audience !== AdminBroadcast::AUDIENCE_SPECIFIC) hidden @endif>
                    {!! $userPicker->render() !!}
                </div>
            </section>

            {{-- 2. Message --}}
            <section class="pc-card" aria-labelledby="pc-msg-title">
                <h3 id="pc-msg-title"><span class="pc-step">2</span>Message</h3>
                <p>English is required and is used for anyone whose app language has no translation.</p>

                <div class="pc-langs" role="tablist">
                    @foreach($languages as $code => $label)
                        <button type="button" role="tab" id="pc-tab-{{ $code }}" aria-controls="pc-pane-{{ $code }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-lang-tab="{{ $code }}">
                            <span class="pc-dot" data-lang-dot="{{ $code }}"></span>
                            {{ $label }}
                            @unless($code === 'en')<span class="pc-opt">optional</span>@endunless
                        </button>
                    @endforeach
                </div>

                @foreach($languages as $code => $label)
                    <div role="tabpanel" id="pc-pane-{{ $code }}" aria-labelledby="pc-tab-{{ $code }}" data-lang-pane="{{ $code }}" @unless($loop->first) hidden @endunless
                         @if($code === 'ar') dir="rtl" @endif>
                        <div class="pc-field">
                            <label for="pc-title-{{ $code }}">Title <span data-counter="title-{{ $code }}" dir="ltr"></span></label>
                            <input type="text" id="pc-title-{{ $code }}" name="broadcast[title][{{ $code }}]" maxlength="255"
                                   value="{{ old('broadcast.title.'.$code) }}" data-input="title" data-lang="{{ $code }}"
                                   placeholder="{{ $code === 'ar' ? 'عنوان الإشعار' : ($code === 'fr' ? 'Titre de la notification' : 'e.g. Doors open at 9:00') }}"
                                   @if($code === 'en') required @endif>
                        </div>
                        <div class="pc-field">
                            <label for="pc-body-{{ $code }}">Message <span data-counter="body-{{ $code }}" dir="ltr"></span></label>
                            <textarea id="pc-body-{{ $code }}" name="broadcast[body][{{ $code }}]" maxlength="2000"
                                      data-input="body" data-lang="{{ $code }}"
                                      placeholder="{{ $code === 'ar' ? 'نص الإشعار' : ($code === 'fr' ? 'Texte de la notification' : 'What do you want people to know?') }}"
                                      @if($code === 'en') required @endif>{{ old('broadcast.body.'.$code) }}</textarea>
                        </div>
                    </div>
                @endforeach
                <p class="pc-tip">Phones show about 50 characters of the title and 180 of the message on the lock screen. Tapping the notification opens the app's notification list, where the full text is shown.</p>
            </section>

            {{-- 3. Send --}}
            <section class="pc-card pc-footer" aria-label="Send">
                <div class="pc-summary" aria-live="polite">
                    <strong data-summary>—</strong>
                    <small data-summary-detail></small>
                </div>
                <div class="pc-actions">
                    {!! $testButton->render() !!}
                    {!! $sendButton->render() !!}
                </div>
            </section>
        </div>

        {{-- Live preview --}}
        <aside class="pc-preview" aria-label="Preview on a phone lock screen">
            <div class="pc-phone">
                <div class="pc-phone-time">9:41</div>
                <div class="pc-phone-date">{{ now()->format('l, F j') }}</div>
                <div class="pc-notif">
                    <div class="pc-notif-head"><span class="pc-notif-icon"></span> {{ config('app.name') }} <time>now</time></div>
                    <div class="pc-notif-title" data-preview-title></div>
                    <div class="pc-notif-body" data-preview-body></div>
                </div>
            </div>
            <p class="pc-preview-note" data-preview-note>Preview in English</p>
        </aside>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('push-compose');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';

    const counts = JSON.parse(root.dataset.counts || '{}');
    const LIMITS = { title: 50, body: 180 };
    const LANG_NAMES = { en: 'English', fr: 'French', ar: 'Arabic' };
    let activeLang = 'en';

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => Array.from(root.querySelectorAll(sel));
    const field = (kind, lang) => root.querySelector(`[data-input="${kind}"][data-lang="${lang}"]`);
    const picker = $('[data-picker]');
    const pickerSelect = () => picker.querySelector('select');
    const sendBtn = $('.pc-actions button[data-button-confirm]') || $$('.pc-actions button').pop();

    function audience() {
        const checked = root.querySelector('input[name="broadcast[audience]"]:checked');
        return checked ? checked.value : 'all';
    }

    function selectedUsers() {
        const select = pickerSelect();
        return select ? Array.from(select.selectedOptions).filter((o) => o.value).length : 0;
    }

    function updateAudience() {
        const value = audience();
        picker.hidden = value !== 'specific';

        const selected = selectedUsers();
        const selectedEl = $('[data-selected-count]');
        if (selectedEl) selectedEl.textContent = selected.toLocaleString();

        const total = value === 'specific' ? selected : (counts[value]?.total ?? 0);
        const reachable = value === 'specific' ? null : (counts[value]?.reachable ?? 0);
        const label = { all: 'all users', visitors: 'visitors', exhibitors: 'exhibitors', specific: 'selected user(s)' }[value];

        $('[data-summary]').textContent = `Will be sent to ${total.toLocaleString()} ${label}`;
        $('[data-summary-detail]').textContent = reachable === null
            ? 'Only people with notifications on will see it on their phone.'
            : `${reachable.toLocaleString()} have notifications on; everyone sees it in the app's notification list.`;

        if (sendBtn) {
            sendBtn.dataset.buttonConfirm = `Send this notification to ${total.toLocaleString()} ${label}? This cannot be undone.`;
        }
    }

    function updateCounters() {
        ['en', 'fr', 'ar'].forEach((lang) => {
            ['title', 'body'].forEach((kind) => {
                const input = field(kind, lang);
                const counter = root.querySelector(`[data-counter="${kind}-${lang}"]`);
                const length = input.value.length;
                counter.textContent = `${length} / ${LIMITS[kind]}`;
                counter.classList.toggle('is-over', length > LIMITS[kind]);
            });
            const title = field('title', lang).value.trim();
            const body = field('body', lang).value.trim();
            const dot = root.querySelector(`[data-lang-dot="${lang}"]`);
            dot.classList.toggle('is-filled', !!(title && body));
            dot.classList.toggle('is-partial', !!(title || body) && !(title && body));
            dot.title = title && body ? 'Filled' : (title || body ? 'Needs both a title and a message' : 'Empty');
        });
    }

    function updatePreview() {
        let lang = activeLang;
        let title = field('title', lang).value.trim();
        let body = field('body', lang).value.trim();
        let note = `Preview in ${LANG_NAMES[lang]}`;

        if (!title && !body && lang !== 'en') {
            title = field('title', 'en').value.trim();
            body = field('body', 'en').value.trim();
            note = `No ${LANG_NAMES[lang]} text yet: these users get the English version`;
        }

        const titleEl = $('[data-preview-title]');
        const bodyEl = $('[data-preview-body]');
        titleEl.textContent = title || 'Your title';
        bodyEl.textContent = body || 'Your message appears here as you type.';
        titleEl.classList.toggle('pc-notif-empty', !title);
        bodyEl.classList.toggle('pc-notif-empty', !body);
        $('[data-preview-note]').textContent = note;
    }

    function selectLang(lang) {
        activeLang = lang;
        $$('[data-lang-tab]').forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.langTab === lang)));
        $$('[data-lang-pane]').forEach((pane) => { pane.hidden = pane.dataset.langPane !== lang; });
        updatePreview();
    }

    $$('[data-lang-tab]').forEach((tab) => tab.addEventListener('click', () => selectLang(tab.dataset.langTab)));
    root.addEventListener('input', () => { updateCounters(); updatePreview(); });
    root.addEventListener('change', updateAudience);

    // The user picker (Tom Select) updates its <select> after async searches.
    const observer = new MutationObserver(updateAudience);
    const select = pickerSelect();
    if (select) observer.observe(select, { childList: true, subtree: true, attributes: true });

    // A required field in a hidden language tab: show that tab instead of a silent block.
    root.addEventListener('invalid', (event) => {
        const lang = event.target.dataset?.lang;
        if (lang && lang !== activeLang) selectLang(lang);
    }, true);

    updateAudience();
    updateCounters();
    updatePreview();
})();
</script>
