/* global jQuery, window */

(function($) {

    $(function () {

        var alert = $('.js-wpml-tm-post-edit-alert');

        if (0 === alert.length) {
            return;
        }

        alert.dialog({
                         dialogClass  : 'otgs-ui-dialog wpml-tm-post-edit-dialog',
                         closeOnEscape: false,
			draggable: false,
			modal: true,
			minWidth: 900,
			open: function(e) {
				$(e.target).closest('.otgs-ui-dialog').find('.ui-widget-header').remove();
        setTimeout(function() {
          if ($(document.activeElement).is('button, a')) {
            $(document.activeElement).blur();
          }
        }, 0);
			}
		});

		alert.on('click', '.js-wpml-tm-go-back', function(e) {
			e.preventDefault();
			dismiss_translation_editor_notice();
			window.history.go(-1);
		}).on('click', '.js-wpml-tm-continue', function(e) {
			e.preventDefault();
			dismiss_translation_editor_notice();
			alert.dialog('close');
		}).on( 'click', '.js-wpml-tm-open-in-te', function() {
			dismiss_translation_editor_notice();
		} );

		function dismiss_translation_editor_notice() {

			var show_again_checkbox = $( '.do-not-show-again' );

			if ( show_again_checkbox.prop('checked') ) {

				var action = show_again_checkbox.attr( 'data-action' );

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: action,
						nonce: $( '#'+action ).val()
					}
				});
			}
		}

	});

})(jQuery);
