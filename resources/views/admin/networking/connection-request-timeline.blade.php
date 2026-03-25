<style>
    .networking-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 20px;
    }

    .networking-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 11px;
        width: 2px;
        background: #e2e8f0;
    }

    .networking-timeline-item {
        position: relative;
        margin-bottom: 24px;
    }

    .networking-timeline-item:last-child {
        margin-bottom: 0;
    }

    .networking-timeline-icon {
        position: absolute;
        left: -30px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 0 0 4px #fff, 0 6px 18px rgba(15, 23, 42, 0.12);
    }

    .networking-timeline-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.05);
    }

    .networking-timeline-item.is-future .networking-timeline-card {
        background: #f8fafc;
        border-style: dashed;
    }
</style>

<div class="networking-timeline">
    @foreach($timeline as $item)
        <div class="networking-timeline-item {{ !empty($item['is_future']) ? 'is-future' : '' }}">
            <div class="networking-timeline-icon" style="background-color: {{ $item['color'] }}">
                <i class="bi {{ $item['icon'] }}"></i>
            </div>

            <div class="networking-timeline-card">
                <div class="fw-semibold text-dark mb-1">{{ $item['title'] }}</div>
                <div class="text-muted small mb-2">{!! $item['description'] !!}</div>
                <div class="small text-uppercase text-muted">{{ $item['date'] }}</div>
            </div>
        </div>
    @endforeach
</div>
