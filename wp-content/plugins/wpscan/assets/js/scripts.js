// Actions for metabox Summary

jQuery( document ).ready(
	function( $ ) {

		let button_check          = $( '#wpscan-metabox-summary .check-now button' );
		let security_check        = $( '.security-check-actions .button' );
		let spinner           	  = $( '#wpscan-metabox-summary .spinner' );
		let security_check_runnig = false;
		let security_check_button = [];

		// Checks if a cron job is already running when the page loads
		if ( wpscan.doing_cron === 'YES' ) {
			button_check.attr( 'disabled', true );
			spinner.css( 'visibility', 'visible' );

			check_cron();
		}

		if ( wpscan.doing_security_cron.length !== 0 ) {
			check_security_cron();
		}

		// Starts the cron job
		function do_check() {
			button_check.attr( 'disabled', true );
			spinner.css( 'visibility', 'visible' );

			$.ajax(
				{
					url: wpscan.ajaxurl,
					method: 'POST',
					data: {
						action: wpscan.action_check,
						_ajax_nonce: wpscan.ajax_nonce
					},
					success: function( ) {
						check_cron();
					},
					error: function () {
						location.reload();
					}
				}
			);

		}

		// Check every X seconds if cron has finished
		function check_cron() {

			setTimeout(
				function() {
					$.ajax(
						{
							url: ajaxurl,
							method: 'POST',
							data: {
								action: wpscan.action_cron,
								_ajax_nonce: wpscan.ajax_nonce
							},
							success: function( data ) {
								if ( data === 'NO' ) {
									location.reload();
								} else {
									check_cron();
								}
							},
							error: function ( ) {
								location.reload();
							}
						}
					);
				},
				1000 * 2
			);

		}

		function check_security_cron() {
			security_check_runnig = true;
			security_check_button = [];
			setTimeout(
				function() {
					$.ajax(
						{
							url: ajaxurl,
							method: 'POST',
							data: {
								action: wpscan.action_security_check,
								_ajax_nonce: wpscan.ajax_nonce
							},
							success: function( data ) {
								if ( data.length !== 0 ) {
									var ajax_response = $.parseJSON( data );
									$.each(
										ajax_response.inline,
										function ( key, data ) {
											security_check_button.push( key );
										}
									);

									$( '.security-check-actions button[data-action="run"]' ).each(
										function() {
											if ( $.inArray( $( this ).data( 'check-id' ), security_check_button ) === -1 && $( this ).attr( 'disabled' ) ) {
												$( this ).closest( 'tr' ).find( '.check-column' ).html( ajax_response.plugins[$( this ).data( 'check-id' )]['status'] );
												$( this ).closest( 'tr' ).find( '.vulnerabilities' ).html( ajax_response.plugins[$( this ).data( 'check-id' )]['vulnerabilities'] );
												$( this ).closest( 'tr' ).find( '.security-check-actions' ).html( ajax_response.plugins[$( this ).data( 'check-id' )]['security-check-actions'] );
											}
										}
									);

									if ( security_check_button.length !== 0 ) {
										check_security_cron();
									} else {
										location.reload();
									}
								}
							},
							error: function ( ) {
								location.reload();
							}
						}
					);
				},
				2000
			);

		}

		// Button
		button_check.on( 'click', do_check );
		if ( ! security_check_runnig ) {
			security_check.one( 'click', check_security_cron );
		}

		// close postboxes that should be closed
		$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
		// postboxes setup
		postboxes.add_postbox_toggles( 'wpscan' );
	}
);
