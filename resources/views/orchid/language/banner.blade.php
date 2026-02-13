<link rel="stylesheet" href="{{ asset('css/language-management.css') }}">

<div class="language-banner">
    <div class="d-flex align-items-start">
        <div class="me-4">
            <div style="background: rgba(255, 255, 255, 0.2);
                        border-radius: 12px;
                        padding: 1rem;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;">
                <i class="bi bi-translate" style="font-size: 2.5rem; color: white;"></i>
            </div>
        </div>
        <div class="flex-grow-1">
            <h4 class="mb-2" style="font-weight: 600;">
                <i class="bi bi-globe2 me-2"></i>Language Management
            </h4>
            <p class="mb-3" style="opacity: 0.95; font-size: 0.95rem; line-height: 1.6;">
                Configure app languages and manage translation files. Enabled languages will be available
                in the mobile app language selector. Changes sync automatically to connected devices.
            </p>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center" style="background: rgba(255,255,255,0.15); padding: 0.75rem; border-radius: 8px;">
                        <i class="bi bi-lightning-charge-fill me-2" style="font-size: 1.25rem;"></i>
                        <div>
                            <small style="opacity: 0.9; display: block; font-size: 0.75rem;">Sync Time</small>
                            <strong style="font-size: 0.9rem;">~5 minutes</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center" style="background: rgba(255,255,255,0.15); padding: 0.75rem; border-radius: 8px;">
                        <i class="bi bi-phone-fill me-2" style="font-size: 1.25rem;"></i>
                        <div>
                            <small style="opacity: 0.9; display: block; font-size: 0.75rem;">Manual Refresh</small>
                            <strong style="font-size: 0.9rem;">Pull to refresh</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center" style="background: rgba(255,255,255,0.15); padding: 0.75rem; border-radius: 8px;">
                        <i class="bi bi-file-earmark-code-fill me-2" style="font-size: 1.25rem;"></i>
                        <div>
                            <small style="opacity: 0.9; display: block; font-size: 0.75rem;">Format</small>
                            <strong style="font-size: 0.9rem;">JSON</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.2);">
                <small style="opacity: 0.9;">
                    <i class="bi bi-lightbulb-fill me-1"></i>
                    <strong>Pro Tips:</strong>
                    Use consistent key naming (e.g., <code>screen_name.element_name</code>).
                    Test translations in the mobile app before deploying to production.
                </small>
            </div>
        </div>
    </div>
</div>
