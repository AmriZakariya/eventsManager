{{-- resources/views/admin/appointment/timeline.blade.php --}}
<style>
    .cc-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 20px;
        font-family: 'Inter', sans-serif;
    }
    .cc-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 11px;
        width: 2px;
        background: #e2e8f0;
    }
    .cc-timeline-item {
        position: relative;
        margin-bottom: 30px;
    }
    .cc-timeline-item:last-child {
        margin-bottom: 0;
    }
    .cc-timeline-icon {
        position: absolute;
        left: -30px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        z-index: 2;
        box-shadow: 0 0 0 4px #ffffff, 0 2px 4px rgba(0,0,0,0.1);
    }
    .cc-timeline-content {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .cc-timeline-item.is-future .cc-timeline-content {
        background: #f8fafc;
        border-style: dashed;
        opacity: 0.8;
    }
    .cc-timeline-title {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .cc-timeline-desc {
        font-size: 13px;
        color: #475569;
        margin-bottom: 8px;
        line-height: 1.5;
    }
    .cc-timeline-date {
        font-size: 11px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<div class="cc-timeline">
    @foreach($timeline as $item)
        <div class="cc-timeline-item {{ $item['is_future'] ?? false ? 'is-future' : '' }}">
            <div class="cc-timeline-icon" style="background-color: {{ $item['color'] }}">
                <i class="bi {{ $item['icon'] }}"></i>
            </div>
            <div class="cc-timeline-content">
                <div class="cc-timeline-title">{{ $item['title'] }}</div>
                <div class="cc-timeline-desc">{!! $item['description'] !!}</div>
                <div class="cc-timeline-date"><i class="bi bi-clock me-1"></i> {{ $item['date'] }}</div>
            </div>
        </div>
    @endforeach
</div>
