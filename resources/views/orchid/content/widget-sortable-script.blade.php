<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.querySelector('table tbody');

        if (tableBody) {
            new Sortable(tableBody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const order = [];
                    const rows = tableBody.querySelectorAll('tr');

                    rows.forEach((row, index) => {
                        const link = row.querySelector('a[href*="/edit/"]');
                        if (link) {
                            const href = link.getAttribute('href');
                            const id = href.match(/\/edit\/(\d+)/)?.[1];
                            if (id) {
                                order.push(id);
                            }
                        }
                    });

                    // Send AJAX request to update order
                    fetch(window.location.pathname + '/update-order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ order: order })
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Order updated successfully');

                            // Update order numbers in the UI
                            rows.forEach((row, index) => {
                                const badge = row.querySelector('.badge.bg-secondary');
                                if (badge) {
                                    badge.textContent = index + 1;
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Error updating order:', error);
                            alert('Failed to update order. Please refresh the page.');
                        });
                }
            });
        }
    });
</script>

<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #f8f9fa;
    }

    .drag-handle:hover {
        color: #333 !important;
    }

    table tbody tr {
        transition: background-color 0.2s;
    }

    table tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
