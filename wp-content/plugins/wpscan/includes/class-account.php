<?php

class WPScan_Account extends WPScan {

  /*
  * Initialize
  */
  static public function init() {

    add_action( 'wpscan/api/get/after', array( __CLASS__, 'update_account_status' ), 10, 2 );
    add_action( 'load-' . self::$page_hook, array( __CLASS__, 'add_account_summary_meta_box' ) );

  }

  /*
  * Update account status after the api request
  */
  static public function update_account_status( $endpoint, $response ) {

    // Update only in the last verify call
    $wordpress_endpoit = '/wordpresses/' . str_replace( '.', '', get_bloginfo( 'version' ) );

    if ($endpoint === $wordpress_endpoit) {

      $current = get_option( self::OPT_ACCOUNT_STATUS, array() );
      $updated = $current;
      
      $updated['limit'] = wp_remote_retrieve_header( $response, 'x-ratelimit-limit' );
      $updated['remaining'] = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
      $updated['reset'] = wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );
      
      if ( ! isset($current['plan']) || $current['limit'] !== $updated['limit']) {
        $req = self::api_get( '/status' );
          
        // Plan
        if (! is_numeric( $req )) {
          $updated['plan'] = $req->plan;
        }

        // For enterprise users
        if ( $req->requests_remaining == -1 ) {
          $updated['limit'] = __('unlimited', 'wpscan');
          $updated['remaining'] = _('unlimited', 'wpscan');
          $updated['reset'] = __('unlimited', 'wpscan');
        }

      }
      
      update_option( self::OPT_ACCOUNT_STATUS, $updated );

    }

  }
  

  /*
  * Add Meta Box
  */
  static public function add_account_summary_meta_box() {

    if ( WPScan_Settings::api_token_set() ) {

      add_meta_box(
        'wpscan-account-summary',
        __( 'Account Status', 'wpscan' ),
        array( __CLASS__, 'do_meta_box_account_summary' ),
        'wpscan',
        'side',
        'low'
      );
  
    }
  }

  /*
  * Get account status
  */
  static public function get_account_status() {
    
    $defaults = array(
      'plan' => 'NO DATA',
      'limit' => 50,
      'remaining' => 50,
      'reset' => time()
    );
    
    return get_option( self::OPT_ACCOUNT_STATUS, $defaults);

  }

  /*
  * Meta Box
  */
  static public function do_meta_box_account_summary() {
    
    extract(self::get_account_status());
    
    if ($plan !== 'enterprise') {

      // If data is not available
      if ( ! isset($limit) || ! is_numeric($limit) ) return;

      // Reset time in hours
      $diff = $reset - time();
      $days = floor( $diff / (60*60*24) );
      $hours = round( ($diff-$days*60*60*24) / (60*60) );
      $hours_display = $hours > 1 ? __( 'Hours', 'wpscan') : __( 'Hour', 'wpscan');

      // Used
      $used = $limit - $remaining;

      // Usage percentage
      $percentage = $limit !== 0 ? ($used * 100) / $limit : 0;
      
      // Usage color
      if ($percentage < 50) {
        $usage_color = 'wpscan-status-green';
      } else if ($percentage >= 50 && $percentage < 95) {
        $usage_color = 'wpscan-status-orange';
      } else {
        $usage_color = 'wpscan-status-red';
      }

    } else { // For enterprise users

      $used = $limit;
      $hours = $reset;
      $hours_display = NULL;
      $usage_color = 'wpscan-status-green';
    
    }

    // Upgrade button
    $btn_text = $plan == 'free' ? __( 'Upgrade', 'wpscan') : __( 'Manage', 'wpscan');
    $btn_url = WPSCAN_PROFILE_URL;

    // Output data
    echo "<ul>";
      echo "<li>". __( 'Plan', 'wpscan') ."<span> $plan </span></li>";
      
      if ($plan !== 'enterprise') {
        echo "<li>". __( 'Usage', 'wpscan') ."<span class='$usage_color'> $used / $limit </span></li>";
        echo "<li>". __( 'Resets In', 'wpscan') ."<span> $hours $hours_display </span></li>";
      }
    echo "</ul>";

    // Output upgrade/manage button
    echo "<a class='button button-primary' href='$btn_url' target='_blank'>$btn_text</a>";

  }

}
