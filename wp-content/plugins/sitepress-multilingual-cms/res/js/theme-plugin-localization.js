/* globals icl_ajx_saved, icl_ajx_error, ajaxurl */

jQuery(function($){

	'use strict';

	$(document).ready(function () {
		var ajax_success_action = function( response, response_text ) {

			if( response.success ) {
				response_text.text( icl_ajx_saved );
			} else {
				response_text.text( icl_ajx_error );
			}

			response_text.show();

			setTimeout(function () {
				response_text.fadeOut('slow');
			}, 2500);
		};

		$( '#wpml-js-theme-plugin-save-option' ).click(function(){

			var alert_scan_new_strings = $( 'input[name*="wpml_st_display_strings_scan_notices"]' ),
				use_theme_plugin_domain = $( 'input[name*="use_theme_plugin_domain"]' ),
				theme_localization_load_textdomain = $( 'input[name*="theme_localization_load_textdomain"]' ),
				gettext_theme_domain_name = $( 'input[name*="gettext_theme_domain_name"]' ),
				response_text = $( '#wpml-js-theme-plugin-options-response' ),
				spinner = $( '#wpml-js-theme-plugin-options-spinner' );

			spinner.addClass( 'is-active' );

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpml_update_localization_options',
					nonce: $( '#wpml-localization-options-nonce' ).val(),
					wpml_st_display_strings_scan_notices: 'checked' === alert_scan_new_strings.attr( 'checked' ) ? alert_scan_new_strings.val() : 0,
					use_theme_plugin_domain: 'checked' === use_theme_plugin_domain.attr( 'checked' ) ? use_theme_plugin_domain.val() : 0,
					theme_localization_load_textdomain: 'checked' === theme_localization_load_textdomain.attr( 'checked' ) ? theme_localization_load_textdomain.val() : 0,
					gettext_theme_domain_name: gettext_theme_domain_name.val()

				},
				success: function ( response ) {
					spinner.removeClass( 'is-active' );
					ajax_success_action( response, response_text );
				}
			});
		});

		$('#theme_localization_load_textdomain').on('change', function() {
			$('input[name="gettext_theme_domain_name"]').prop('disabled', !$(this).is(':checked'));
		});
	});
});