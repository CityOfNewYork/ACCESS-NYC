
// Actions for metabox Summary

jQuery( document ).ready( function( $ ) {

  let button_check = $( '#wpscan-metabox-summary .check-now button' );
  let spinner = $( '#wpscan-metabox-summary .spinner' );

  // Checks if a cron job is already running when the page loads
  
  if ( wpscan.doing_cron == 'YES' ) {
    button_check.attr( 'disabled', true );
    spinner.css( 'visibility', 'visible' );

    check_cron();
  }

  // Starts the cron job

  function do_check() {

    button_check.attr( 'disabled', true );
    spinner.css( 'visibility', 'visible' );

    $.ajax( {
      url: wpscan.ajaxurl,
      method: 'POST',
      data: {
        action: wpscan.action_check,
        _ajax_nonce: wpscan.ajax_nonce
      },
      success: function( ) {
        check_cron();
      }
    } );

  }

  // Check every X seconds if cron has finished

  function check_cron() {

    setTimeout( function() {
      $.ajax( {
        url: ajaxurl,
        method: 'POST',
        data: {
          action: wpscan.action_cron,
          _ajax_nonce: wpscan.ajax_nonce
        },
        success: function( data ) {
          if ( data === 'NO' )
            location.reload();
          else
            check_cron();
        },
        error: function ( ) {
          check_cron();
        }
      } );
    }, 1000 * 2);

  }

  // Button

  button_check.on( 'click', do_check );

    // close postboxes that should be closed
    $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
    // postboxes setup
    postboxes.add_postbox_toggles('wpscan');
});