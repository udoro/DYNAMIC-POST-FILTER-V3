jQuery(document).ready(function($) {
    function updateBricksQuery() {
        const filterContainer = $(this).closest('.dynamic-filter');
        const targetElement = filterContainer.data('target');
        
        if (!targetElement || !window.bricks) {
            console.error('Bricks Builder not found or no target element specified');
            return;
        }

        const tax_query = {};
        filterContainer.find('.filter-select').each(function() {
            const taxonomy = $(this).data('taxonomy');
            const value = $(this).val();
            if (value) {
                tax_query[taxonomy] = value;
            }
        });

        // Get the Bricks query instance
        const bricksInstance = window.bricks.queryLoopInstances?.[targetElement];
        if (!bricksInstance) {
            console.error('Bricks query instance not found');
            return;
        }

        // Update query parameters
        const queryData = {
            tax_query: tax_query
        };

        // Reset pagination
        if (bricksInstance.page) {
            bricksInstance.page = 1;
        }

        // Merge with existing query
        bricksInstance.queryArgs = {
            ...bricksInstance.queryArgs,
            ...queryData
        };

        // Run the query
        bricksInstance.run();
    }

    // Attach event handlers
    $(document).on('change', '.filter-select', updateBricksQuery);

    // Initialize filters if needed
    $('.dynamic-filter').each(function() {
        const targetElement = $(this).data('target');
        if (targetElement && window.bricks?.queryLoopInstances?.[targetElement]) {
            // Initial query setup if needed
            const bricksInstance = window.bricks.queryLoopInstances[targetElement];
            bricksInstance.queryArgs = bricksInstance.queryArgs || {};
        }
    });
});