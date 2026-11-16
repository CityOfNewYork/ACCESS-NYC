jQuery(function ($) {
    // Add target _blank to documentation link.
    $('#toplevel_page_air-wp-sync-pro-plus').each(function () {
        $(this).find('a[href*="wpconnect.co"]').filter('a[href*="documentation"]').attr('target', '_blank');
    });
});
