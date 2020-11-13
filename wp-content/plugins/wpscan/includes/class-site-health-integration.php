<?php

class WPScan_Sitehealth_integration extends WPScan {

  /*
  * Initialize
  */
  static public function init() {

    add_filter( 'site_status_tests', array( __CLASS__, 'add_site_health_tests' ) );

  }

  /**
   * Add site-health page tests
   * 
   * @return array
   */
  static public function add_site_health_tests( $tests ) {
    
    $tests['direct']['wpscan_check'] = array(
      'label' => __( 'WPScan Vulnerabilities Check' ),
      'test'  => array( __CLASS__, "site_health_tests" ),
    );
  
    return $tests;
  
  }

  /**
   * Do site-health page tests
   * 
   * @return array
   */
  static public function site_health_tests() {

    $report = self::$report;
    $total = self::get_total();
    $vulns = WPScan_Report::get_all_vulnerabilities();

    /**
     * Default state, no vulnerabilities found.
     */
    $result = array(
      'label'       => __( 'No known vulnerabilities found' ),
      'status'      => 'good',
      'badge'       => array(
          'label' => __( 'Security' ),
          'color' => 'gray',
      ),
      'description' => sprintf(
          '<p>%s</p>',
          __( 'Vulnerabilities can be exploited by hackers and cause harm to your website.' )
      ),
      'actions'     => '',
      'test'        => 'wpscan_check',
    );
    
    /**
     * if vulnerabilities found.
     */
    if ( ! empty( $report ) && $total > 0 ) {

        $result['status'] = 'critical';

        $result['label'] = sprintf( _n( 'Your site is affected by %d security vulnerability', 'Your site is affected by %d security vulnerabilities', $total, 'wpscan' ), $total );
        
        $result['description'] = 'WPScan detected the following security vulnerabilities in your site:';

        foreach ($vulns as $vuln ) {

          $result['description'] .= "<p>";

          $result['description'] .= "<span class='dashicons dashicons-warning' style='color: crimson;'></span> &nbsp";

          $result['description'] .= "<a style='text-decoration: none;' href='$vuln[1]'>$vuln[0]</a>";

          $result['description'] .= "</p>";
        
        }
    }

    return $result;
  }

}