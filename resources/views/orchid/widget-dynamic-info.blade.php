<div class="alert alert-info">
    <div class="d-flex align-items-center">
        <i class="bs.database me-3" style="font-size: 24px;"></i>
        <div>
            <h5 class="alert-heading mb-2">Dynamic Content Enabled</h5>
            <p class="mb-1">This section automatically displays content from:
                <strong class="text-primary">{{ $type }}</strong>
            </p>
            <p class="mb-0 small text-muted">
                Items are populated automatically based on your {{ $source }} data.
                To manage content, please go to the {{ ucfirst($source) }} management section.
            </p>
        </div>
    </div>
</div>
