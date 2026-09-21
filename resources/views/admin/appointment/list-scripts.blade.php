{{-- resources/views/admin/appointment/list-scripts.blade.php --}}
{{-- Double-click any appointment row (except on buttons/links) to open its detail page. --}}
@push('scripts')
    <script>
        (function () {
            function bindAppointmentRowDblClick() {
                document.querySelectorAll('tr').forEach(function (row) {
                    var anchor = row.querySelector('.appointment-row-link[data-detail-url]');
                    if (!anchor || row.dataset.dblclickBound) {
                        return;
                    }
                    row.dataset.dblclickBound = '1';
                    row.style.cursor = 'default';

                    row.addEventListener('dblclick', function (event) {
                        // Ignore double-clicks that land on interactive controls.
                        if (event.target.closest('a, button, input, select, textarea, .btn')) {
                            return;
                        }
                        var url = anchor.getAttribute('data-detail-url');
                        if (url) {
                            window.location.assign(url);
                        }
                    });
                });
            }

            document.addEventListener('turbo:load', bindAppointmentRowDblClick);
            document.addEventListener('turbo:render', bindAppointmentRowDblClick);
        })();
    </script>
@endpush
