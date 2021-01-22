<?php

class WPScan_Dashboard extends WPScan {

  /*
  * Initialize
  */
  static public function init() {

    add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widgets' ) );

  }

  // Function used in the action hook
  static public function add_dashboard_widgets() {

    if ( ! current_user_can( self::WPSCAN_ROLE ) ) {
      return;
    }

    wp_add_dashboard_widget(self::WPSCAN_DASHBOARD, 'WPScan Status', array( __CLASS__, 'dashboard_widget_content' ) );

  }

  static public function dashboard_widget_content( $post, $callback_args ) {

    if ( ! WPScan_Settings::api_token_set() ) {
      echo '<div>' . __( 'To use WPScan you have to setup your WPScan API Token.', 'wpscan' ) . '</div>';
      return;
    }

    if ( empty( self::$report ) ) {
      echo __( 'No Report available', 'wpscan' );
      return;
    }

    $vulns = WPScan_Report::get_all_vulnerabilities();

    if ( empty( $vulns ) ) {
      echo __( 'No vulnerabilities found', 'wpscan' );
    }

    echo '<div>';
    foreach( $vulns as $vuln) {
      echo '<div><span class="dashicons dashicons-warning is-red"></span>&nbsp;<a href="' . esc_url($vuln[1]) .'" target="_blank">' . esc_html( $vuln[0] ) . '</a></div>';
    }
    echo '</div>';

  }
}
