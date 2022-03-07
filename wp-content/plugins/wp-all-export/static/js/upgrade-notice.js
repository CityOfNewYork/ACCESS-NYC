(function($){$(function () {
    window.openUpgradeNotice = function(addon, element, preloaderSrc) {
        $('.wpallexport-overlay').show();
        $('.wpallexport-loader').show();

        var $self = element;
        $.ajax({
            type: "POST",
            url: ajaxurl,
            context: element,
            data: {
                'action': 'wpae_upgrade_notice',
                'addon': addon,
                'security' : wp_all_export_security
            },
            success: function (data) {

                $('.wpallexport-loader').hide();
                $(this).pointer({
                    content: '<div id="wpallexport-upgrade-notice">' + data + '</div>',
                    position: {
                        edge: 'right',
                        align: 'center'
                    },
                    pointerWidth: 815,
                    show: function (event, t) {
                        var $leftOffset = ($(window).width() - 715) / 2;
                        var $topOffset = $(document).scrollTop() + 100;

                        var $pointer = $('.wp-pointer').last();
                        $pointer.css({'position': 'absolute', 'top': $topOffset + 'px', 'left': $leftOffset + 'px'});
                    
                        $('.already-have-link').on('click', function() {
                            $pointer.find('.upgrade').hide();
                            $pointer.find('.install').show();
                        });

                        $('.custom-close').on('click', function() {
                            element.pointer('close');
                        });
                    },
                    close: function () {
                        jQuery('.wpallexport-overlay').hide();
                    }
                }).pointer('open');
                $('.wp-pointer-buttons').hide();
            },
            error: function () {
                $('#pmxe_button_preloader').remove();
                $('.close-pointer').show();
                $(".wpallexport-overlay").trigger('click');
                $('.wpallexport-loader').hide();
            }
        });
	};
});})(jQuery);