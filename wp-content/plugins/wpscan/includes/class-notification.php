<?php

class WPScan_Notification extends WPScan {

  // Page slug
  static private $page;

  /*
  * Initialize
  */
  static public function init() {

    self::$page = 'wpscan_notification';

    add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
    add_action( 'load-' . self::$page_hook, array( __CLASS__, 'add_meta_box_notification' ) );

  }

  /*
  * Notification Options
  */
  static public function admin_init() {

    $total = self::get_total();

    register_setting( self::$page, self::OPT_EMAIL, array( __CLASS__, 'sanitize_email' ) );
    register_setting( self::$page, self::OPT_INTERVAL, array( __CLASS__, 'sanitize_interval' ) );
    register_setting( self::$page, self::OPT_IGNORED, array( __CLASS__, 'sanitize_ignored' ) );

    $section = self::$page . '_section';

    add_settings_section(
      $section,
      null,
      array( __CLASS__, 'introduction' ),
      self::$page
    );

    add_settings_field(
      self::OPT_EMAIL,
      __( 'E-mail', 'wpscan' ),
      array( __CLASS__, 'field_email' ),
      self::$page,
      $section
    );

    add_settings_field(
      self::OPT_INTERVAL,
      __( 'Send Alerts', 'wpscan' ),
      array( __CLASS__, 'field_interval' ),
      self::$page,
      $section
    );

    if ( $total > 0 ) {
      add_settings_field(
        self::OPT_IGNORED,
        __( 'Vulnerabilities to Ignore', 'wpscan' ),
        array( __CLASS__, 'field_ignored' ),
        self::$page,
        $section
      );
    }

  }

  /*
  * Add Meta Box
  */
  static public function add_meta_box_notification() {

    add_meta_box(
      'metabox-notification',
      __( 'Notification', 'wpscan' ),
      array( __CLASS__, 'do_meta_box_notification' ),
      'wpscan',
      'side',
      'low'
    );

  }

  /*
  * Meta Box
  */
  static public function do_meta_box_notification() {

    echo '<form action="options.php" method="post">';
    settings_fields( self::$page );
    do_settings_sections( self::$page );
    submit_button();
    echo '</form>';

  }

  /*
  * Introduction
  */
  static public function introduction() {

    echo '<p>' . __( 'Fill in the options below if you want to be notified by mail about new vulnerabilities. To add multiple e-mail addresses comma separate them.', 'wpscan' ) . '</p>';

  }

  /*
  * Field E-mail
  */
  static public function field_email() {

    echo sprintf(
      '<input type="text" name="%s" value="%s" class="regular-text" placeholder="email@domain.com, copy@domain.com">',
      self::OPT_EMAIL,
      esc_attr( get_option( self::OPT_EMAIL, '' ) )
    );

  }

  /*
  * Field Interval
  */
  static public function field_interval() {

    $interval = get_option( self::OPT_INTERVAL, 'd' );

    echo '<select name="' . self::OPT_INTERVAL . '">';
    echo '<option value="o" ' . selected( 'o', $interval, false ) . '>' . __( 'Disabled', 'wpscan' ) . '</option>';
    echo '<option value="d" ' . selected( 'd', $interval, false ) . '>' . __( 'Daily', 'wpscan' ) . '</option>';
    echo '<option value="1" ' . selected( 1, $interval, false ) . '>' . __( 'Every Monday', 'wpscan' ) . '</option>';
    echo '<option value="2" ' . selected( 2, $interval, false ) . '>' . __( 'Every Tuesday', 'wpscan' ) . '</option>';
    echo '<option value="3" ' . selected( 3, $interval, false ) . '>' . __( 'Every Wednesday', 'wpscan' ) . '</option>';
    echo '<option value="4" ' . selected( 4, $interval, false ) . '>' . __( 'Every Thursday', 'wpscan' ) . '</option>';
    echo '<option value="5" ' . selected( 5, $interval, false ) . '>' . __( 'Every Friday', 'wpscan' ) . '</option>';
    echo '<option value="6" ' . selected( 6, $interval, false ) . '>' . __( 'Every Saturday', 'wpscan' ) . '</option>';
    echo '<option value="7" ' . selected( 7, $interval, false ) . '>' . __( 'Every Sunday', 'wpscan' ) . '</option>';
    echo '<option value="m" ' . selected( 'm', $interval, false ) . '>' . __( 'Every Month', 'wpscan' ) . '</option>';
    echo '</selected>';

  }

  /*
  * Field Ignore
  */
  static public function field_ignored() {

    self::list_vulnerabilities_to_ignore( 'wordpress', get_bloginfo( 'version' ) );

    foreach ( get_plugins() as $name => $details ) {
      self::list_vulnerabilities_to_ignore( 'plugins', self::get_plugin_slug( $name, $details ) );
    }

    foreach ( wp_get_themes() as $name => $details ) {
      self::list_vulnerabilities_to_ignore( 'themes', self::get_theme_slug( $name, $details ) );
    }

  }

  /*
  * List of vulnerabilities
  *
  * @param string $type - Type of report: wordpress, plugins, themes
  * @param string $name - key name of the element
  * @return string
  */
  static public function list_vulnerabilities_to_ignore( $type, $name ) {

    $report = self::$report[ $type ];
    if ( array_key_exists( $name, $report ) ) {
      $report = $report[ $name ];
    }

    if ( ! isset( $report['vulnerabilities'] ) ) {
      return null;
    }

    $ignored = get_option( self::OPT_IGNORED, array() );

    foreach ( $report['vulnerabilities'] as $item ) {
      echo sprintf(
        '<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label><br>',
        self::OPT_IGNORED,
        $item->id,
        in_array( $item->id, $ignored ) ? 'checked="checked"' : null,
        self::get_vulnerability_title( $item )
      );
    }

  }

  /*
  * Sanitize Email
  */
  static public function sanitize_email( $value ) {

    if ( ! empty( $value ) ) {

      $emails = explode( ',', $value );

      foreach ( $emails as $email ) {
        if ( ! is_email( trim( $email ) ) ) {
          add_settings_error( self::OPT_EMAIL, 'invalid-email', __( 'You have entered an invalid e-mail address.', 'wpscan' ) );
          $value = '';
        }
      }

    }

    return $value;

  }

  /*
  * Sanitize Interval
  */
  static public function sanitize_interval( $value ) {

    $allowed_values = array( 'o', 'd', 1, 2, 3, 4, 5, 6, 7, 'm' );
    if ( !in_array($value, $allowed_values) ) {
      // return default value
      return 'd';
    }
    return $value;

  }

  /*
  * Sanitize Ignored
  */
  static public function sanitize_ignored( $value ) {

    // this should be an empty array if nothing is checked
    if ( empty( $value ) ) {
      return array();
    }

    return $value;

  }

  /*
  * Return the total of vulnerabilities found but not ignored
  */
  static public function get_total_not_ignored() {

    $report = self::$report;
    $ignored = get_option( self::OPT_IGNORED, array() );

    $total = self::get_total();

    $types = array( 'wordpress', 'plugins', 'themes' );

    foreach($types as $type) {
      if ( $report[$type]['total'] > 0 ) {
        foreach ( $report[$type] as $item ) {
          if ( empty( $item['vulnerabilities'] ) ) {
            continue;
          }
          foreach ( $item['vulnerabilities'] as $vuln ) {
            if ( in_array( $vuln->id, $ignored ) ) {
              $total -= 1;
            }
          }
        }
      }
    }

    return $total;

  }

  /*
  * Sending notification
  */
  static public function notify() {

    // Suppports during WP Cron
    if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $report = self::$report;
    $total = self::get_total_not_ignored();

    if ( $total == 0 ) {
      return;
    }

    $email = get_option( self::OPT_EMAIL );
    $interval = get_option( self::OPT_INTERVAL, 'd' );

    // Check email or if notifications are disabled
    if ( empty( $email ) || $interval === 'o' ) {
      return;
    }

    // Check weekly interval
    if ( is_numeric( $interval ) && date( 'N' ) !== $interval ) {
      return;
    }

    // Check monthly interval
    if ( $interval == 'm' && date( 'j' ) !== 1 ) {
      return;
    }

    // Send email
    $has_vulnerabilities = false;
    $msg = '<!doctype html><html><head><meta charset="utf-8"></head><body>';
    $msg .= '<p>' . __( 'Hello,', 'wpscan' ) . '</p>';
    $msg .= '<p>' . sprintf( __( 'Some vulnerabilities were found in %s, visit the site for more details.', 'wpscan' ), '<a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'name' ) . '</a>' ) . '</p>';

    // WordPress
    if ( $report['wordpress']['total'] > 0 ) {
      $list = self::email_vulnerabilities( 'wordpress', get_bloginfo( 'version' ) );
      if ( ! empty( $list ) ) {
        $has_vulnerabilities = true;
        $msg .= '<p><b>WordPress</b><br>';
        $msg .= join( '<br>', $list ) . '</p>';
      }
    }

    // Plugins
    if ( $report['plugins']['total'] > 0 ) {
      foreach ( get_plugins() as $name => $details ) {
        $slug = self::get_plugin_slug( $name, $details );
        $list = self::email_vulnerabilities( 'plugins', $slug );
        if ( ! empty( $list ) ) {
          $has_vulnerabilities = true;
          $msg .= '<p><b>' . __( 'Plugin', 'wpscan' ) . ' ' . $details['Name'] . '</b><br>';
          $msg .= join( '<br>', $list ) . '</p>';
        }
      }
    }

    // Themes
    if ( $report['themes']['total'] > 0 ) {
      foreach ( wp_get_themes() as $name => $details ) {
        $slug = self::get_theme_slug( $name, $details );
        $list = self::email_vulnerabilities( 'themes', $slug );
        if ( ! empty( $list ) ) {
          $has_vulnerabilities = true;
          $msg .= '<p><b>' . __( 'Theme', 'wpscan' ) . ' ' . $details['Name'] . '</b><br>';
          $msg .= join( '<br>', $list ) . '</p>';
        }
      }
    }

    $msg .= '</body></html>';

    if ( $has_vulnerabilities ) {
      $subject = sprintf( __( 'Some vulnerabilities were found in %s', 'wpscan' ), get_bloginfo( 'name' ) );
      $headers = array( 'Content-Type: text/html; charset=UTF-8' );
      wp_mail( $email, $subject, $msg, $headers );
    }

  }

  /*
  * List of vulnerabilities to send by mail
  */
  static public function email_vulnerabilities( $type, $name ) {

    $report = self::$report[ $type ];
    if ( array_key_exists( $name, $report ) ) {
      $report = $report[ $name ];
    }

    if ( ! isset( $report['vulnerabilities'] ) ) {
      return null;
    }

    $ignored = get_option( self::OPT_IGNORED, array() );

    $list = array();

    foreach ( $report['vulnerabilities'] as $item ) {
      if ( ! in_array( $item->id, $ignored ) ) {
        $html = '<a href="' . esc_url( 'https://wpscan.com/vulnerabilities/' . $item->id ) . '" target="_blank">';
        $html .= esc_html( self::get_vulnerability_title( $item ) );
        $html .= '</a>';
        $list[] = $html;
      }
    }

    return $list;

  }

}
