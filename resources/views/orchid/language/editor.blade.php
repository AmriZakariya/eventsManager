<div class="translation-editor">
    <!-- Language Header -->
    <div class="alert alert-gold mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-globe2 me-3" style="font-size: 2rem; color: #000;"></i>
            <div class="flex-grow-1">
                <h5 class="mb-1" style="font-weight: 600; color: #000;">
                    Editing: {{ strtoupper($languageCode) }} Translations
                </h5>
                <p class="mb-0" style="opacity: 0.9; font-size: 0.9rem; color: #000;">
                    <i class="bi bi-info-circle me-1"></i>
                    Changes are saved manually using the "Save Changes" button above
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-text me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <div class="stats-label">Total Keys</div>
                            <div class="stats-value" id="translationCount">
                                {{ count($translations) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle me-3" style="font-size: 1.5rem; color: #10b981;"></i>
                        <div>
                            <div class="stats-label">Status</div>
                            <div class="stats-value" style="font-size: 1.1rem;">
                                Ready to Edit
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn w-100 h-100 btn-info-custom" data-bs-toggle="collapse" data-bs-target="#commonKeys">
                <i class="bi bi-book me-2"></i>
                Common Keys Reference
            </button>
        </div>
    </div>

    <!-- Common Keys Reference (Collapsible) -->
    <div class="collapse mb-4" id="commonKeys">
        <div class="card common-keys">
            <div class="card-body">
                <h6 class="mb-3">
                    <i class="bi bi-lightbulb me-2"></i>Common Translation Keys
                </h6>
                <div class="row g-4">
                    <div class="col-md-4">
                        <h6>Authentication</h6>
                        <div style="line-height: 1.8;">
                            <div><code>welcome_title</code></div>
                            <div><code>welcome_subtitle</code></div>
                            <div><code>email_label</code></div>
                            <div><code>password_label</code></div>
                            <div><code>login_btn</code></div>
                            <div><code>signup_link</code></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Navigation</h6>
                        <div style="line-height: 1.8;">
                            <div><code>home</code></div>
                            <div><code>agenda</code></div>
                            <div><code>exhibitors</code></div>
                            <div><code>networking</code></div>
                            <div><code>profile</code></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Common Actions</h6>
                        <div style="line-height: 1.8;">
                            <div><code>save</code></div>
                            <div><code>cancel</code></div>
                            <div><code>submit</code></div>
                            <div><code>delete</code></div>
                            <div><code>edit</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(empty($translations))
        <div class="alert alert-warning-custom">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>No translations found</strong>
                    <p class="mb-0 mt-1" style="opacity: 0.9;">
                        Start by adding your first translation key using the button below.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Translation Editor -->
    <div class="card editor-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-translate me-2"></i>Translation Keys
                </h5>
                <div class="d-flex gap-2">
                    <input type="file" id="jsonFileInput" accept=".json" style="display: none;" onchange="handleFileUpload(event)">
                    <button type="button" class="btn btn-sm btn-info-custom" onclick="document.getElementById('jsonFileInput').click()">
                        <i class="bi bi-upload me-1"></i> Import JSON
                    </button>
                    <button type="button" class="btn btn-sm btn-success-custom" onclick="addTranslationRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Key
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllRows()">
                        <i class="bi bi-trash me-1"></i> Clear All
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 translation-table">
                    <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">
                            Key <span class="text-danger">*</span>
                        </th>
                        <th style="width: 50%;">
                            Translation <span class="text-danger">*</span>
                        </th>
                        <th style="width: 10%; text-align: center;">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="translationRows">
                    @forelse($translations as $index => $translation)
                        @php
                            // Handle both array format ['key' => '...', 'value' => '...']
                            // and direct key-value pairs from iteration
                            if (is_array($translation) && isset($translation['key'])) {
                                $translationKey = $translation['key'];
                                $translationValue = is_array($translation['value']) ? json_encode($translation['value']) : $translation['value'];
                            } else {
                                $translationKey = is_string($index) ? $index : '';
                                $translationValue = is_array($translation) ? json_encode($translation) : $translation;
                            }
                        @endphp
                        <tr class="translation-row" data-index="{{ $index }}">
                            <td class="text-center">
                                <span class="row-number">{{ is_numeric($index) ? $index + 1 : $loop->iteration }}</span>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="form-control form-control-sm translation-key"
                                    name="translations[{{ $loop->index }}][key]"
                                    value="{{ $translationKey }}"
                                    placeholder="e.g., welcome_message"
                                    required
                                >
                            </td>
                            <td>
                                    <textarea
                                        class="form-control form-control-sm translation-value"
                                        name="translations[{{ $loop->index }}][value]"
                                        rows="2"
                                        placeholder="Enter translation..."
                                        required
                                    >{{ $translationValue }}</textarea>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Delete this translation">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="translation-row" data-index="0">
                            <td class="text-center">
                                <span class="row-number">1</span>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="form-control form-control-sm translation-key"
                                    name="translations[0][key]"
                                    value="welcome"
                                    placeholder="e.g., welcome_message"
                                    required
                                >
                            </td>
                            <td>
                                    <textarea
                                        class="form-control form-control-sm translation-value"
                                        name="translations[0][value]"
                                        rows="2"
                                        placeholder="Enter translation..."
                                        required
                                    >Welcome</textarea>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Press <kbd>Tab</kbd> to move between fields quickly
                </small>
                <small id="unsavedChanges" style="display: none;">
                    <i class="bi bi-exclamation-circle me-1"></i>Unsaved changes
                </small>
            </div>
        </div>
    </div>

    <!-- Save Reminder -->
    <div class="alert alert-blue mt-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Remember:</strong> Click the "Save Changes" button at the top to save your translations.
    </div>
</div>

<script>
    let rowIndex = {{ count($translations) }};
    let hasUnsavedChanges = false;

    // Handle JSON file upload
    function handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.name.endsWith('.json')) {
            alert('Please upload a valid JSON file');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const jsonData = JSON.parse(e.target.result);

                // Validate JSON structure
                if (typeof jsonData !== 'object' || Array.isArray(jsonData)) {
                    alert('Invalid JSON format. Expected an object with key-value pairs.');
                    return;
                }

                // Confirm before replacing
                const currentRows = document.querySelectorAll('.translation-row').length;
                if (currentRows > 0) {
                    const confirmMessage = `This will replace all ${currentRows} existing translation(s). Continue?`;
                    if (!confirm(confirmMessage)) {
                        event.target.value = ''; // Reset file input
                        return;
                    }
                }

                // Clear existing rows
                document.getElementById('translationRows').innerHTML = '';
                rowIndex = 0;

                // Add all translations from JSON
                let count = 0;
                for (const [key, value] of Object.entries(jsonData)) {
                    addTranslationRowWithData(key, value);
                    count++;
                }

                // Show success message
                const message = `Successfully imported ${count} translation(s)`;
                if (window.Orchid && window.Orchid.notification) {
                    window.Orchid.notification.show({
                        type: 'success',
                        message: message
                    });
                } else {
                    showCustomNotification('success', message);
                }

                hasUnsavedChanges = true;
                document.getElementById('unsavedChanges').style.display = 'inline';
                updateTranslationCount();

            } catch (error) {
                alert('Error parsing JSON file: ' + error.message);
                console.error('JSON parse error:', error);
            }

            // Reset file input
            event.target.value = '';
        };

        reader.onerror = function() {
            alert('Error reading file');
        };

        reader.readAsText(file);
    }

    // Add translation row with pre-filled data
    function addTranslationRowWithData(key, value) {
        const tbody = document.getElementById('translationRows');
        const newRow = document.createElement('tr');
        newRow.className = 'translation-row';
        newRow.setAttribute('data-index', rowIndex);

        // Ensure value is a string
        const valueStr = typeof value === 'object' ? JSON.stringify(value) : String(value);

        newRow.innerHTML = `
            <td class="text-center">
                <span class="row-number">${rowIndex + 1}</span>
            </td>
            <td>
                <input
                    type="text"
                    class="form-control form-control-sm translation-key"
                    name="translations[${rowIndex}][key]"
                    value="${escapeHtml(key)}"
                    placeholder="e.g., welcome_message"
                    required
                >
            </td>
            <td>
                <textarea
                    class="form-control form-control-sm translation-value"
                    name="translations[${rowIndex}][value]"
                    rows="2"
                    placeholder="Enter translation..."
                    required
                >${escapeHtml(valueStr)}</textarea>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Delete this translation">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        rowIndex++;

        // Add change tracking to new inputs
        newRow.querySelectorAll('.translation-key, .translation-value').forEach(input => {
            input.addEventListener('input', function() {
                hasUnsavedChanges = true;
                document.getElementById('unsavedChanges').style.display = 'inline';
            });
        });
    }

    // Escape HTML for safe insertion
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

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

    // Track changes
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.translation-key, .translation-value');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                hasUnsavedChanges = true;
                document.getElementById('unsavedChanges').style.display = 'inline';
            });
        });

        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        updateTranslationCount();
    });

    function addTranslationRow() {
        const tbody = document.getElementById('translationRows');
        const newRow = document.createElement('tr');
        newRow.className = 'translation-row';
        newRow.setAttribute('data-index', rowIndex);

        newRow.innerHTML = `
            <td class="text-center">
                <span class="row-number">${rowIndex + 1}</span>
            </td>
            <td>
                <input
                    type="text"
                    class="form-control form-control-sm translation-key"
                    name="translations[${rowIndex}][key]"
                    placeholder="e.g., welcome_message"
                    required
                >
            </td>
            <td>
                <textarea
                    class="form-control form-control-sm translation-value"
                    name="translations[${rowIndex}][value]"
                    rows="2"
                    placeholder="Enter translation..."
                    required
                ></textarea>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Delete this translation">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(newRow);
        rowIndex++;

        // Focus on the new key input
        const newKeyInput = newRow.querySelector('.translation-key');
        newKeyInput.focus();

        // Add change tracking to new inputs
        newRow.querySelectorAll('.translation-key, .translation-value').forEach(input => {
            input.addEventListener('input', function() {
                hasUnsavedChanges = true;
                document.getElementById('unsavedChanges').style.display = 'inline';
            });
        });

        updateRowNumbers();
        updateTranslationCount();
        hasUnsavedChanges = true;
        document.getElementById('unsavedChanges').style.display = 'inline';
    }

    function removeRow(button) {
        const row = button.closest('tr');
        const key = row.querySelector('.translation-key').value;

        const confirmMessage = key
            ? `Are you sure you want to remove "${key}"?`
            : 'Are you sure you want to remove this translation?';

        if (confirm(confirmMessage)) {
            row.remove();
            updateRowNumbers();
            updateTranslationCount();
            hasUnsavedChanges = true;
            document.getElementById('unsavedChanges').style.display = 'inline';
        }
    }

    function clearAllRows() {
        if (confirm('Are you sure you want to clear all translations? This action cannot be undone.')) {
            document.getElementById('translationRows').innerHTML = '';
            rowIndex = 0;
            addTranslationRow(); // Add one empty row
            hasUnsavedChanges = true;
            document.getElementById('unsavedChanges').style.display = 'inline';
        }
    }

    function updateRowNumbers() {
        const rows = document.querySelectorAll('.translation-row');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').textContent = index + 1;
            row.setAttribute('data-index', index);
        });
    }

    function updateTranslationCount() {
        const count = document.querySelectorAll('.translation-row').length;
        const countElement = document.getElementById('translationCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }

    // Handle language selector change
    const languageSelector = document.querySelector('select[name="languageCode"]');
    if (languageSelector) {
        languageSelector.addEventListener('change', function() {
            if (hasUnsavedChanges) {
                if (confirm('You have unsaved changes. Do you want to leave without saving?')) {
                    window.location.href = window.location.pathname + '?languageCode=' + this.value;
                } else {
                    // Reset to previous value
                    this.value = '{{ $languageCode }}';
                }
            } else {
                window.location.href = window.location.pathname + '?languageCode=' + this.value;
            }
        });
    }
</script>
