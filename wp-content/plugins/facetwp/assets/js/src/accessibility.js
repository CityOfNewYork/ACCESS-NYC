(function($) {
    $(document).on('facetwp-loaded', function() {
        $('.facetwp-checkbox').each(function(index, $el) {
            $el.attr('role', 'checkbox');
            $el.attr('aria-checked', $el.hasClass('checked') ? 'true' : 'false');
            $al.attr('tabindex', index);
        });
    });
})(jQuery);