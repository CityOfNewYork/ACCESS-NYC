(function( wp, $ ) {
    'use strict';

    if ( ! wp ) {
        return;
    }

    function activatePlugin( url, el ,elInfo) {
        var message = el.data( 'message' );
        var link = el.data( 'add-link' );
        el.removeClass('emr-plugin-button');
        var linkName = el.data( 'add-link-name' );

        $.ajax( {
            async: true,
            type: 'GET',
            dataType: 'html',
            url: url,
            success: function() {
                el.removeClass( 'emr-updating' );
                el.text( message );
                elInfo.after("<br><br><a href='" + link + "' target='_blank'>" + linkName + "</a>");
            }
        } );
    }

    function emrInstallPlugin(slug) {
        var data = { action  : 'emr_install_plugin', slug: slug};
        jQuery.get(ajaxurl, data, function(response) {
            console.log(response);
        });
    }

    $( function() {
        $( document ).on( 'click', '.emr-plugin-button', function( event ) {
            var action = $( this ).data( 'action' ),
                url = $( this ).attr( 'href' ),
                slug = $( this ).data( 'slug' );

            event.preventDefault();

            if ( 'install' === action ) {

                $( this ).addClass( 'emr-updating disabled' );

                wp.updates.installPlugin( {
                    slug: slug
                } );

            } else if ( 'activate' === action ) {

                $( this ).addClass( 'emr-updating disabled' );
                emrInstallPlugin(slug);
                activatePlugin( url, $( this ), $("#" + slug +"-info"));

            }

        } );

        $( document ).on( 'wp-plugin-install-success', function( response, data ) {
            var el = $( '.emr-plugin-button[data-slug="' + data.slug + '"]' );
            emrInstallPlugin(data.slug);
            activatePlugin( data.activateUrl, el , $("#" + data.slug + "-info"));
            if(typeof event !== 'undefined') {
                event.preventDefault();
            }
        } );

    } );
})( window.wp, jQuery );

