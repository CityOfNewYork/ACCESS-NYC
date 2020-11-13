<?php

class WPScan_Summary extends WPScan {

  /*
  * Initialize
  */
  static public function init() {

    add_action( 'load-' . self::$page_hook, array( __CLASS__, 'add_meta_box_summary' ) );
    add_action( 'wp_ajax_' . self::WPSCAN_ACTION_CHECK, array( __CLASS__, 'ajax_check_now' ) );
    add_action( 'wp_ajax_' . self::WPSCAN_TRANSIENT_CRON, array( __CLASS__, 'ajax_doing_cron' ) );

  }
  

  /*
  * Add Meta Box
  */
  static public function add_meta_box_summary() {

    if ( empty( self::$report ) )
      return;

    add_meta_box(
      'metabox-summary',
      __( 'Summary', 'wpscan' ),
      array( __CLASS__, 'do_meta_box_summary' ),
      'wpscan',
      'side',
      'high'
    );

  }

  /*
  * Meta Box
  */
  static public function do_meta_box_summary() {

    $errorset = isset( self::$report['error'] );
    $total = self::get_total();
    ?>

    <p>
      <?php _e( 'The last request to the', 'wpscan' ) ?> <a href="https://wpscan.com/" target="_blank">WPScan Vulnerability Database</a> <?php _e( 'was:', 'wpscan' ) ?> 
    </p>
    <p>
      <span class="dashicons dashicons-calendar-alt"></span> <strong><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), self::$report['cache'] ) ?></strong>
    </p>

    <?php
    if ( $errorset ) {
      foreach( self::$report['error'] as $err ) {
        // $err should not contain user input. If you like to add an esc_html() here, be sure to update the error text that use HTML
        echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . $err . '</strong></p>';
      }
    } elseif ( ! $errorset && $total == 0 ) {
      echo '<p class="wpscan-summary-res is-green"><span class="dashicons dashicons-awards"></span> <strong>' . __( 'No known vulnerabilities found', 'wpscan' ) . '</strong></p>';
    } elseif( ! get_option( self::OPT_API_TOKEN ) ) {
      echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'You need to add a WPScan API Token to the settings page', 'wpscan' ) . '</strong></p>';
    } else {
      echo '<p class="wpscan-summary-res is-red"><span class="dashicons dashicons-megaphone"></span> <strong>' . __( 'Some vulnerabilities were found', 'wpscan' ) . '</strong></p>';
    }
    ?>

    <p class="description">
      <?php _e( 'Click the Check Now button to run a vulnerability scan against your WordPress version, installed plugins and themes.', 'wpscan' ) ?>
    </p>

    <?php if ( get_option( self::OPT_API_TOKEN ) ) : ?>
    <p class="check-now">
      <span class="spinner"></span>
      <button type="button" class="button button-primary"><?php _e( 'Check Now', 'wpscan' ) ?></button>
    </p>
    <?php endif ?>

    <?php

  }

  /*
  * Ajax Check Now button
  */
  static public function ajax_check_now() {
    check_ajax_referer( self::WPSCAN_SCRIPT );

    if ( !current_user_can( self::WPSCAN_ROLE ) ) {
      wp_redirect( home_url() );
      wp_die();
    }

    self::check_now();
    wp_die();;

  }

  /*
  * Check when the cron task has finished
  */
  static public function ajax_doing_cron() {
    check_ajax_referer( self::WPSCAN_SCRIPT );

    if ( !current_user_can( self::WPSCAN_ROLE ) ) {
      wp_redirect( home_url() );
      wp_die();
    }

    echo get_transient( self::WPSCAN_TRANSIENT_CRON ) ? 'YES' : 'NO';
    wp_die();;

  }

}
