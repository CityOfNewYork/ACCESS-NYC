/**
 * Query string manipulation library
 * @url https://github.com/sindresorhus/query-string
 */
(function(){"use strict";var e={};e.parse=function(e){if(typeof e!=="string"){return{}}e=e.trim().replace(/^(\?|#)/,"");if(!e){return{}}return e.trim().split("&").reduce(function(e,t){var n=t.replace(/\+/g," ").split("=");var r=n[0];var i=n[1];r=decodeURIComponent(r);i=i===undefined?null:decodeURIComponent(i);if(!e.hasOwnProperty(r)){e[r]=i}else if(Array.isArray(e[r])){e[r].push(i)}else{e[r]=[e[r],i]}return e},{})};e.stringify=function(e){return e?Object.keys(e).map(function(t){var n=e[t];if(Array.isArray(n)){return n.map(function(e){return encodeURIComponent(t)+"="+encodeURIComponent(e)}).join("&")}return encodeURIComponent(t)+"="+encodeURIComponent(n)}).join("&"):""};if(typeof define==="function"&&define.amd){define(function(){return e})}else if(typeof module!=="undefined"&&module.exports){module.exports=e}else{window.queryString=e}})();

(function($) {

    $(document).on('facetwp-refresh', function() {
        if (! FWP.loaded) {
            setup_woocommerce();
        }
    });

    function setup_woocommerce() {

        // Intercept WooCommerce pagination
        $(document).on('click', '.woocommerce-pagination a.page-numbers', function(e) {
            e.preventDefault();
            var matches = $(this).attr('href').match(/\/page\/(\d+)/);
            if (null != matches) {
                FWP.paged = parseInt(matches[1]);
            }
            FWP.soft_refresh = true;
            FWP.refresh();
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