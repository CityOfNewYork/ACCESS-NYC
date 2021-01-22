<?php

class WPScan_Admin_Bar extends WPScan {

  /*
  * Initialize
  */
  static public function init() {

    add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 65 ); // Between Updates, Comments and New Content menu

  }

  /*
  * Create a shortcut on Admin Bar to show the total of vulnerabilities found
  */
  static public function admin_bar( $wp_admin_bar ) {

    if ( ! current_user_can( self::WPSCAN_ROLE ) ) {
      return;
    }

    $report = self::$report;
    $total = self::get_total();

    if ( ! empty( $report ) and $total > 0 ) {
      $args = array(
        'id' => 'wpscan',
        'title' => '<span class="ab-icon dashicons-shield"></span><span class="ab-label">' . $total . '</span>',
        'href' => admin_url( 'admin.php?page=wpscan' ),
        'meta' => array(
          'title' => sprintf( _n( '%d vulnerability found', '%d vulnerabilities found', $total, 'wpscan' ), $total )
        )
      );
      $wp_admin_bar->add_node( $args );
    }

  }

}