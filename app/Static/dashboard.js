document.addEventListener('DOMContentLoaded', function () {
    /**
     * Handles bulk selection checkboxes based on a target category.
     */
    function setupBulkSelect() {
        // Select All handler
        document.querySelectorAll('.btn-select-all').forEach(button => {
            button.addEventListener('click', function () {
                const targetClass = 'filter-checkbox-' + this.getAttribute('data-target');
                document.querySelectorAll('.' + targetClass).forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
        });

        // Unselect All handler
        document.querySelectorAll('.btn-unselect-all').forEach(button => {
            button.addEventListener('click', function () {
                const targetClass = 'filter-checkbox-' + this.getAttribute('data-target');
                document.querySelectorAll('.' + targetClass).forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        });
    }

    // Initialize logic
    setupBulkSelect();

    console.log('Dashboard filter logic initialized.');
});
