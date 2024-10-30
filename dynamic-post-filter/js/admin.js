jQuery(document).ready(function($) {
    let taxonomyCounter = $('.taxonomy-row').length;

    $('#add-taxonomy').on('click', function() {
        const template = $('#taxonomy-row-template').html();
        const newRow = template.replace(/\{\{KEY\}\}/g, 'new_' + taxonomyCounter++);
        $('#dpf-taxonomy-list').append(newRow);
    });

    $(document).on('click', '.remove-taxonomy', function() {
        $(this).closest('.taxonomy-row').remove();
    });

    // Auto-generate slug from name
    $(document).on('input', '[name$="[name]"]', function() {
        const slugInput = $(this).closest('.taxonomy-fields').find('[name$="[slug]"]');
        if (!slugInput.val()) {
            slugInput.val($(this).val().toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, ''));
        }
    });
});