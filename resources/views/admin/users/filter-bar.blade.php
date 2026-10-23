@php
    $fb = $filterBar;
    $url = fn (array $changes) => request()->fullUrlWithQuery($changes + ['page' => null]);
    $clearAll = request()->url() . (request()->filled('sort') ? '?' . http_build_query(['sort' => request('sort')]) : '');
@endphp

<div class="ul-filters" data-user-filters>
    <div class="ul-filters-top">
        <label class="ul-search">
            <x-orchid-icon path="bs.search" class="ul-search-icon"/>
            <input type="search" value="{{ $fb['search'] }}" data-filter="search"
                   placeholder="Search name, email, company, phone, job title…" autocomplete="off">
            @if($fb['search'] !== '')
                <a href="{{ $url(['search' => null]) }}" class="ul-search-clear" title="Clear search"><x-orchid-icon path="bs.x-lg"/></a>
            @endif
        </label>

        <button type="button" class="ul-more-toggle {{ $fb['activeCount'] ? 'is-active' : '' }}" data-filters-toggle
                aria-expanded="{{ $fb['activeCount'] ? 'true' : 'false' }}">
            <x-orchid-icon path="bs.sliders"/>
            <span>More filters</span>
            @if($fb['activeCount'])<span class="ul-count">{{ $fb['activeCount'] }}</span>@endif
        </button>
    </div>

    <nav class="ul-tabs" aria-label="Filter by role">
        <a href="{{ $url(['role' => null]) }}" class="ul-tab {{ $fb['role'] === '' ? 'is-active' : '' }}">
            All <span>{{ number_format($fb['total']) }}</span>
        </a>
        @foreach($fb['roles'] as $value => $label)
            <a href="{{ $url(['role' => $value]) }}" class="ul-tab {{ $fb['role'] === $value ? 'is-active' : '' }}">
                {{ $label }} <span>{{ number_format($fb['roleCounts'][$value] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    <div class="ul-more" data-filters-panel @if(! $fb['activeCount']) hidden @endif>
        @foreach($fb['selects'] as $key => $select)
            <label class="ul-select">
                <span>{{ $select['label'] }}</span>
                <select class="form-select form-select-sm" data-filter="{{ $key }}">
                    <option value="">Any</option>
                    @foreach($select['options'] as $value => $label)
                        <option value="{{ $value }}" @selected(request()->query($key) === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
    </div>

    @if($fb['chips'] || $fb['search'] !== '' || $fb['role'] !== '')
        <div class="ul-chips">
            @if($fb['search'] !== '')
                <a href="{{ $url(['search' => null]) }}" class="ul-chip">Search: “{{ \Illuminate\Support\Str::limit($fb['search'], 30) }}” <x-orchid-icon path="bs.x"/></a>
            @endif
            @if($fb['role'] !== '' && isset($fb['roles'][$fb['role']]))
                <a href="{{ $url(['role' => null]) }}" class="ul-chip">Role: {{ $fb['roles'][$fb['role']] }} <x-orchid-icon path="bs.x"/></a>
            @endif
            @foreach($fb['chips'] as $chip)
                <a href="{{ $url([$chip['key'] => null]) }}" class="ul-chip">{{ $chip['label'] }} <x-orchid-icon path="bs.x"/></a>
            @endforeach
            <a href="{{ $clearAll }}" class="ul-clear">Clear all</a>
        </div>
    @endif
</div>
