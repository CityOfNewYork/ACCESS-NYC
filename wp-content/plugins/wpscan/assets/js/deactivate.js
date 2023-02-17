jQuery(document).ready(function($) {
    let link = $('#deactivate-wpscan');
    let deactivate = $('.wpscan-model .button-deactivate');
    let close = $('.wpscan-model .button-close');

    deactivate.attr('href', link.attr('href'));
    
    link.on('click', function (e) {
        e.preventDefault();

        $('.wpscan-model').show()
    });

    close.on('click', function (e) {
        e.preventDefault();

        $('.wpscan-model').hide()
    });
});