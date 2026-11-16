(function($) {
    function init() {
        $(document).tooltip({
            items: '.airwpsync-tooltip',
            tooltipClass: 'arrow-bottom',
            content: function() {
                return $(this).attr('aria-label');
            },
            position: {
                my: 'center bottom',
                at: 'center-3 top-11',
            },
            open: function (event, ui) {
                self = this;
                if (typeof (event.originalEvent) === 'undefined') {
                    return false;
                }
    
                var $id = ui.tooltip.attr('id');
                $('div.ui-tooltip').not('#' + $id).remove();
            },
            close: function (event, ui) {
                ui.tooltip.hover(function () {
                    $(this).stop(true).fadeTo(400, 1);
                },
                function () {
                    $(this).fadeOut('500', function() {
                        $(this).remove();
                    });
                });
            }
        });
    }
  
    $(init);
})(jQuery);