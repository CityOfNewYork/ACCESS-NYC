<?php

class WPScan_Report extends WPScan {

  // Page slug
  static private $page;

  /*
  * Initialize
  */
  static public function init() {

    self::$page = 'wpscan';

    add_action( 'admin_menu', array( __CLASS__, 'menu' ) );

  }

  /*
  * Admin Menu
  */
  static public function menu() {

    add_submenu_page(
      'wpscan',
      __( 'Report', 'wpscan' ),
      __( 'Report', 'wpscan' ),
      self::WPSCAN_ROLE,
      self::$page,
      array( __CLASS__, 'page' )
    );

  }

  /*
  * Report Page
  */
  static public function page() {

    include self::$plugin_dir . '/views/report.php';

  }

  /*
  * List vulnerabilities on screen
  *
  * @param string $type - Type of report: wordpress, plugins, themes
  * @param string $name - key name of the element
  * @return string
  */
  static public function list_vulnerabilities( $type, $name ) {

    $null_text = __( 'No known vulnerabilities found to affect this version', 'wpscan' );
    $not_checked_text = __( 'Not checked yet. Click the Check Now button to run a scan', 'wpscan' );
    $not_found_text = __( 'Not found in database', 'wpscan' );

    if ( empty( self::$report ) ) return null;

    $report = self::$report[ $type ];
    
    if ( array_key_exists( $name, $report ) ) {
      $report = $report[ $name ];
    } else {
      echo $not_checked_text;
      return;
    }

    if ( isset( $report['vulnerabilities'] ) ) {

      $list = array();
    
      usort($report['vulnerabilities'], array('self', 'sort_vulnerabilities'));
  
      foreach ( $report['vulnerabilities'] as $item ) {
        $html = '<div class="vulnerability">';
  
        $html .= self::vulnerability_severity( $item );
  
        $html .= '<a href="' . esc_url( 'https://wpscan.com/vulnerabilities/' . $item->id ) . '" target="_blank">';
        $html .= esc_html( self::get_vulnerability_title( $item ) );
        $html .= '</a>';
      
        $html .= '</div>';
  
        $list[] = $html;
      }
  
      echo empty( $list ) ? $null_text : join( '<br>', $list );
  
    } elseif ($report['not_found']) {
      echo $not_found_text;
    } else {
      echo $null_text;
    }
  }

 /**
  * Sort vulnerabilities by severity
  */
  static public function sort_vulnerabilities( $a, $b ) {
    $a = isset($a->cvss->score) ? intval($a->cvss->score) : 0;
    $b = isset($b->cvss->score) ? intval($b->cvss->score) : 0;

    return $b > $a ? 1 : -1;
  }

  /*
  * vulnerability severity
  *
  * @return string
  */
  static public function vulnerability_severity( $vulnerability ) {

    $plan = WPScan_Account::get_account_status()['plan'];
    
    if ( $plan !== 'enterprise' ) {
      return;
    }

    $html = "<div class='vulnerability-severity'>";
    
    // Severity
    if ( isset($vulnerability->cvss->severity)) {
      $severity = $vulnerability->cvss->severity;
      
      $html .= "<span class='wpscan-$severity'>$severity</span>";
    }

    $html .= "</div>";

    return $html;
  
  }

  /**
  * Is the plugin/theme is closed
  *
  * @return boolean
  */
  static public function is_item_closed( $type, $name ) {
    
    if ( empty( self::$report ) )
    return null;

    $report = self::$report[ $type ];
    if ( array_key_exists( $name, $report ) ) {
      $report = $report[ $name ];
    }
    
    return isset($report['closed']) ? $report['closed'] : false;

  }

  /**
  * Get all vulnerabilities
  *
  * @return array
  */
  static public function get_all_vulnerabilities( ) {

    $ret = array();

    if ( empty( self::$report ) ) {
      return $ret;
    }

    $types = array( 'wordpress', 'plugins', 'themes' );
    foreach ($types as $type) {
      $report = self::$report[ $type ];

      foreach($report as $item) {
        if ( ! isset( $item['vulnerabilities'] ) ) {
          continue;
        }

        foreach ( $item['vulnerabilities'] as $vuln ) {
          $url = 'https://wpscan.com/vulnerabilities/' . $vuln->id;
          $title = self::get_vulnerability_title( $vuln );

          $temp = array();
          array_push($temp, $title);
          array_push($temp, $url);

          array_push($ret, $temp);
        }
      }
    }
    return $ret;

  }

  /**
  * Show status icons: checked, attention and error
  *
  * @param string $type - Type of report: wordpress, plugins, themes
  * @param string $name - key name of the element
  * @return string
  */
  static public function get_status( $type, $name ) {

    if ( empty( self::$report ) )
      return null;

    $report = self::$report[ $type ];
    if ( array_key_exists( $name, $report ) ) {
      $report = $report[ $name ];
    }

    if ( ! isset( $report['vulnerabilities'] ) ) {
      return '&nbsp; <span class="dashicons dashicons-no-alt is-gray"></span>';
    } elseif ( empty( $report['vulnerabilities'] ) ) {
      return '&nbsp; <span class="dashicons dashicons-yes is-green"></span>';
    } else {
      return '&nbsp; <span class="dashicons dashicons-warning is-red"></span>';
    }

  }

}