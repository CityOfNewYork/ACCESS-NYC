(function($) {
    $(document).on('facetwp-loaded', function() {
        $('.edd-no-js').hide();
        $('a.edd-add-to-cart').addClass('edd-has-js');
    });
})(jQuery);