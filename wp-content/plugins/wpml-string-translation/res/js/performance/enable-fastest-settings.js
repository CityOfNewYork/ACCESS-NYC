/*globals jQuery, ajaxurl */
jQuery(function($) {
	"use strict";

	$('document').ready(function () {
		$('[data-id="wpml_st_faster_settings"] .button-primary').click(function (event) {

			var enable_button = $(this);
			var notice = enable_button.closest('.otgs-notice');
			var success = notice.find('.js-done');
			var error = notice.find('.js-error');

			event.preventDefault();
			enable_button.attr( 'disabled', true );

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpml_update_localization_options',
					theme_localization_type: 3,
					all_strings_are_english: 1,
					nonce: $( '#wpml-localization-options-nonce' ).val()
				},
				success: function( response ) {
					error.hide();
					success.hide();
					notice.removeClass('notice-error error notice-info info');
					enable_button.attr( 'disabled', false );

					if (response.success) {
						notice.addClass('notice-success');
						notice.addClass('success');

						enable_button.hide();
						success.show();

						setTimeout(function () {
							notice.fadeOut('slow');
						}, 2500);
					} else {
						notice.addClass('notice-error');
						notice.addClass('error');
						error.show();

						if (null !== response.data) {
							error.find('strong').text(response.data);
						}
					}
				}
			});
		});
	});
});