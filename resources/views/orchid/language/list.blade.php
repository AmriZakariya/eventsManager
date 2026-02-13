<div class="languages-grid">
    <!-- Language Cards Grid -->
    <div class="row g-4 mb-4">
        @forelse($languages as $index => $language)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 language-card" data-code="{{ $language['code'] }}">
                    <div class="card-body p-4">
                        <!-- Header with Flag and Name -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="language-flag" style="font-size: 3rem; line-height: 1;">
                                        {{ $language['flag'] }}
                                    </span>
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $language['name'] }}
                                        </h5>
                                        <span class="badge bg-warning text-dark">
                                            {{ strtoupper($language['code']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle Switch -->
                            <div class="form-check form-switch ms-2">
                                <input
                                    class="form-check-input language-toggle"
                                    type="checkbox"
                                    id="lang_{{ $language['code'] }}"
                                    {{ ($language['enabled'] ?? true) ? 'checked' : '' }}
                                    data-code="{{ $language['code'] }}"
                                    data-name="{{ $language['name'] }}"
                                    style="width: 3rem; height: 1.5rem;"
                                    title="Toggle language availability"
                                    {{ $language['code'] === ($settings->language ?? 'en') ? 'disabled' : '' }}
                                >
                            </div>
                        </div>

                        <!-- Status Badges -->
                        <div class="mb-3 d-flex gap-2 flex-wrap">
                            <span class="badge status-badge {{ ($language['enabled'] ?? true) ? 'bg-success' : 'bg-secondary' }}">
                                <i class="bi {{ ($language['enabled'] ?? true) ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                                {{ ($language['enabled'] ?? true) ? 'Active' : 'Disabled' }}
                            </span>

                            @if($language['code'] === ($settings->language ?? 'en'))
                                <span class="badge status-badge" style="background: #D4AF37; color: #000;">
                                    <i class="bi bi-star-fill me-1"></i> Default
                                </span>
                            @endif
                        </div>

                        <!-- Translation Statistics -->
                        @php
                            $translationCount = count($settings->getTranslationFile($language['code']));
                            $completionLevel = $translationCount === 0 ? 'empty' : ($translationCount < 10 ? 'low' : ($translationCount < 50 ? 'medium' : 'high'));
                            $completionColors = [
                                'empty' => ['bg' => '#fee2e2', 'text' => '#dc2626'],
                                'low' => ['bg' => '#fef3c7', 'text' => '#f59e0b'],
                                'medium' => ['bg' => '#dbeafe', 'text' => '#3b82f6'],
                                'high' => ['bg' => '#d1fae5', 'text' => '#10b981']
                            ];
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span style="color: #6b7280; font-size: 0.85rem;">
                                    <i class="bi bi-file-earmark-text me-1"></i> Translations
                                </span>
                                <span style="color: {{ $completionColors[$completionLevel]['text'] }}; font-weight: 600; font-size: 0.9rem;">
                                    {{ $translationCount }} keys
                                </span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar"
                                     role="progressbar"
                                     style="width: {{ min(100, $translationCount) }}%; background: {{ $completionColors[$completionLevel]['text'] }};"
                                     aria-valuenow="{{ $translationCount }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <a
                                href="{{ route('platform.language.management', ['languageCode' => $language['code']]) }}"
                                class="btn btn-sm btn-primary-gold"
                            >
                                <i class="bi bi-pencil me-1"></i> Edit Translations
                            </a>

                            <div class="btn-group" role="group">
                                @if($translationCount > 0)
                                    <a
                                        href="{{ route('platform.language.export', $language['code']) }}"
                                        class="btn btn-sm btn-action"
                                        title="Export as JSON"
                                    >
                                        <i class="bi bi-download me-1"></i> Export
                                    </a>
                                @endif

                                @if($language['code'] !== ($settings->language ?? 'en'))
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete-language"
                                        data-code="{{ $language['code'] }}"
                                        data-name="{{ $language['name'] }}"
                                        title="Delete language"
                                    >
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($language['code'] === ($settings->language ?? 'en'))
                            <small class="d-block mt-2 text-center text-muted">
                                <i class="bi bi-lock-fill me-1"></i>Cannot disable or delete default language
                            </small>
                        @endif
                    </div>

                    <!-- Loading Overlay -->
                    <div class="loading-overlay">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-blue">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1" style="color: #1e40af;">No languages configured yet</h6>
                            <p class="mb-0" style="color: #1e40af;">Click the "Add Language" button above to add your first language.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle language toggle
        document.querySelectorAll('.language-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                const code = this.dataset.code;
                const name = this.dataset.name;
                const enabled = this.checked;
                const card = this.closest('.language-card');
                const statusBadge = card.querySelector('.status-badge');
                const loadingOverlay = card.querySelector('.loading-overlay');

                // Show loading
                loadingOverlay.style.display = 'flex';

                fetch('{{ route('platform.language.toggle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ code, enabled })
                })
                    .then(response => response.json())
                    .then(data => {
                        loadingOverlay.style.display = 'none';

                        if (data.success) {
                            // Update badge
                            if (enabled) {
                                statusBadge.className = 'badge status-badge bg-success';
                                statusBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i> Active';
                            } else {
                                statusBadge.className = 'badge status-badge bg-secondary';
                                statusBadge.innerHTML = '<i class="bi bi-x-circle me-1"></i> Disabled';
                            }

                            // Show success notification
                            if (window.Orchid && window.Orchid.notification) {
                                window.Orchid.notification.show({
                                    type: 'success',
                                    message: `${name} language ${enabled ? 'enabled' : 'disabled'} successfully`
                                });
                            } else {
                                showCustomNotification('success', `${name} language ${enabled ? 'enabled' : 'disabled'}`);
                            }
                        } else {
                            throw new Error(data.message || 'Failed to update language status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingOverlay.style.display = 'none';
                        this.checked = !enabled; // Revert on error

                        if (window.Orchid && window.Orchid.notification) {
                            window.Orchid.notification.show({
                                type: 'error',
                                message: 'Failed to update language status: ' + error.message
                            });
                        } else {
                            showCustomNotification('error', 'Failed to update language status');
                        }
                    });
            });
        });

        // Handle language deletion
        document.querySelectorAll('.btn-delete-language').forEach(function(button) {
            button.addEventListener('click', function() {
                const code = this.dataset.code;
                const name = this.dataset.name;
                const card = this.closest('.language-card');

                if (confirm(`Are you sure you want to delete ${name}? This will remove all translations for this language. This action cannot be undone.`)) {
                    const loadingOverlay = card.querySelector('.loading-overlay');
                    loadingOverlay.style.display = 'flex';

                    fetch(`/admin/language/${code}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Fade out and remove card
                                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.9)';

                                setTimeout(() => {
                                    card.closest('.col-md-6').remove();

                                    if (window.Orchid && window.Orchid.notification) {
                                        window.Orchid.notification.show({
                                            type: 'success',
                                            message: `${name} language deleted successfully`
                                        });
                                    } else {
                                        showCustomNotification('success', `${name} deleted`);
                                    }
                                }, 300);
                            } else {
                                throw new Error(data.message || 'Failed to delete language');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            loadingOverlay.style.display = 'none';

                            if (window.Orchid && window.Orchid.notification) {
                                window.Orchid.notification.show({
                                    type: 'error',
                                    message: 'Failed to delete language: ' + error.message
                                });
                            } else {
                                showCustomNotification('error', 'Failed to delete language');
                            }
                        });
                }
            });
        });
    });

    // Custom notification for when Orchid notification is not available
    function showCustomNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
        notification.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
</script>
