(function($) {

    $(document).on('facetwp-refresh', function() {
        if (! FWP.loaded) {
            setup_woocommerce();
        }
    });

    function setup_woocommerce() {

        // Intercept WooCommerce pagination
        $(document).on('click', '.woocommerce-pagination a', function(e) {
            e.preventDefault();
            var matches = $(this).attr('href').match(/\/page\/(\d+)/);
            if (null !== matches) {
                FWP.paged = parseInt(matches[1]);
                FWP.soft_refresh = true;
                FWP.refresh();
            }
        });

        // Disable sort handler
        $('.woocommerce-ordering').off('change', 'select.orderby');

        // Intercept WooCommerce sorting
        $(document).on('change', '.woocommerce-ordering .orderby', function(e) {
            var url_obj = queryString.parse(window.location.search);
            url_obj.orderby = $(this).val();
            history.pushState(null, null, window.location.pathname + '?' + queryString.stringify(url_obj));
            FWP.soft_refresh = true;
            FWP.refresh();
        });
    }
})(jQuery);