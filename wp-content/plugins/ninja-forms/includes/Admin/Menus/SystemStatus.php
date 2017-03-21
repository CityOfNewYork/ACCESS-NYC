<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_SystemStatus extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $menu_slug = 'nf-system-status';

    public $priority = 12;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_page_title()
    {
        return __( 'Get Help', 'ninja-forms' );
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_status_capabilities', $this->capability );
    }

    public function display()
    {
        /** @global wpdb $wpdb */
        global $wpdb;

        wp_enqueue_style( 'nf-admin-system-status', Ninja_Forms::$url . 'assets/css/admin-system-status.css' );
        wp_enqueue_script( 'nf-admin-system-status-script', Ninja_Forms::$url . 'assets/js/admin-system-status.js', array( 'jquery' ) );
        //PHP locale
        $locale = localeconv();

        if ( is_multisite() ) {
            $multisite = __( 'Yes', 'ninja-forms' );
        } else {
            $multisite = __( 'No', 'ninja-forms' );
         }

         //TODO: Possible refactor
         foreach( $locale as $key => $val ){
             if( is_string( $val ) ){
                $data = $key . ': ' . $val . '</br>';
             }
         }

         //TODO: Ask if this check is need
         //if ( function_exists( 'phpversion' ) ) echo esc_html( phpversion() );

         //WP_DEBUG
         if ( defined('WP_DEBUG') && WP_DEBUG ){
             $debug = __( 'Yes', 'ninja-forms' );
         } else {
            $debug =  __( 'No', 'ninja-forms' );
         }

         //WPLANG
         if ( defined( 'WPLANG' ) && WPLANG ) {
            $lang = WPLANG;
         } else {
            $lang = __( 'Default', 'ninja-forms' );
         }

         //TODO: Ask if this long list of ini_get checks are need?

        //  if( function_exists( 'ini_get' ) ){
        //     $get_ini = size_format( ini_get('post_max_size') );
        //  }

        //SUHOSIN
        if ( extension_loaded( 'suhosin' ) ) {
            $suhosin =  __( 'Yes', 'ninja-forms' );
        } else {
            $suhosin =  __( 'No', 'ninja-forms' );
        }


        //Time Zone Check
        //TODO: May need refactored
        $default_timezone = get_option( 'timezone_string' );

        //Check for active plugins
        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }

        $all_plugins = array();

        foreach ( $active_plugins as $plugin ) {
            $plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            $dirname        = dirname( $plugin );
            $version_string = '';

            if ( ! empty( $plugin_data['Name'] ) ) {

                // link the plugin name to the plugin url if available
                $plugin_name = $plugin_data['Name'];
                if ( ! empty( $plugin_data['PluginURI'] ) ) {
                    $plugin_name = '<a href="' . $plugin_data['PluginURI'] . '" title="' . __( 'Visit plugin homepage' , 'ninja-forms' ) . '">' . $plugin_name . '</a>';
                }

                $all_plugins[] = $plugin_name . ' ' . __( 'by', 'ninja-forms' ) . ' ' . $plugin_data['Author'] . ' ' . __( 'version', 'ninja-forms' ) . ' ' . $plugin_data['Version'] . $version_string;
            }
        }

        if ( sizeof( $all_plugins ) == 0 ) {
            $site_wide_plugins = '-';
        } else {
            $site_wide_plugins = implode( ', <br/>', $all_plugins );
        }

        $server_ip = $_SERVER['SERVER_ADDR'];
        $host_name = gethostbyaddr( $server_ip );

        //Output array
        $environment = array(
            __( 'Home URL','ninja-forms' ) => home_url(),
            __( 'Site URL','ninja-forms' ) => site_url(),
            __( 'Ninja Forms Version','ninja-forms' ) => esc_html( Ninja_Forms::VERSION ),
            __( 'WP Version','ninja-forms' ) => get_bloginfo('version'),
            __( 'WP Multisite Enabled','ninja-forms' ) => $multisite,
            __( 'Web Server Info','ninja-forms' ) => esc_html( $_SERVER['SERVER_SOFTWARE'] ),
            __( 'PHP Version','ninja-forms' ) => esc_html( phpversion() ),
            //TODO: Possibly Refactor with Ninja forms global $_db?
            __( 'MySQL Version','ninja-forms' ) => $wpdb->db_version(),
            __( 'PHP Locale','ninja-forms' ) =>  $data,
            //TODO: Possibly move the ninja_forms_letters_to_numbers function over.
            __( 'WP Memory Limit','ninja-forms' ) => WP_MEMORY_LIMIT,
            __( 'WP Debug Mode', 'ninja-forms' ) => $debug,
            __( 'WP Language', 'ninja-forms' ) => $lang,
            __( 'WP Max Upload Size','ninja-forms' ) => size_format( wp_max_upload_size() ),
            __('PHP Post Max Size','ninja-forms' ) => ini_get( 'post_max_size' ),
            __('Max Input Nesting Level','ninja-forms' ) => ini_get('max_input_nesting_level'),
            __('PHP Time Limit','ninja-forms' ) => ini_get('max_execution_time'),
            __( 'PHP Max Input Vars','ninja-forms' ) => ini_get('max_input_vars'),
            __( 'SUHOSIN Installed','ninja-forms' ) => $suhosin,
            __( 'Server IP Address', 'ninja-forms' ) => $server_ip,
            __( 'Host Name', 'ninja-forms' ) => $host_name,
            __( 'SMTP','ninja-forms' ) => ini_get('SMTP'),
            __( 'smtp_port','ninja-forms' ) => ini_get('smtp_port'),
            __( 'Default Timezone','ninja-forms' ) => $default_timezone,
        );

        Ninja_Forms::template( 'admin-menu-system-status.html.php', compact( 'environment', 'site_wide_plugins' ) );
    }
} // End Class NF_Admin_SystemStatus
