<?php
/*
Plugin Name: Ninja Forms
Plugin URI: http://ninjaforms.com/
Description: Ninja Forms is a webform builder with unparalleled ease of use and features.
Version: 3.0.18
Author: The WP Ninjas
Author URI: http://ninjaforms.com
Text Domain: ninja-forms
Domain Path: /lang/

Copyright 2016 WP Ninjas.
*/

require_once dirname( __FILE__ ) . '/lib/NF_VersionSwitcher.php';
require_once dirname( __FILE__ ) . '/lib/NF_Tracking.php';
require_once dirname( __FILE__ ) . '/lib/NF_Conversion.php';
require_once dirname( __FILE__ ) . '/lib/Conversion/Calculations.php';

function ninja_forms_three_table_exists(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'nf3_forms';
    return ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name );
}

if( get_option( 'ninja_forms_load_deprecated', FALSE ) && ! ( isset( $_POST[ 'nf2to3' ] ) && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ){

    include 'deprecated/ninja-forms.php';

    register_activation_hook( __FILE__, 'ninja_forms_activation_deprecated' );

    function ninja_forms_activation_deprecated( $network_wide ){
        include_once 'deprecated/includes/activation.php';

        ninja_forms_activation( $network_wide );
    }

} else {

    include_once 'lib/NF_Upgrade.php';
    include_once 'lib/NF_AddonChecker.php';

    include_once plugin_dir_path( __FILE__ ) . 'includes/deprecated.php';

    /**
     * Class Ninja_Forms
     */
    final class Ninja_Forms
    {

        /**
         * @since 3.0
         */
        const VERSION = '3.0.18';

        /**
         * @var Ninja_Forms
         * @since 2.7
         */
        private static $instance;

        /**
         * Plugin Directory
         *
         * @since 3.0
         * @var string $dir
         */
        public static $dir = '';

        /**
         * Plugin URL
         *
         * @since 3.0
         * @var string $url
         */
        public static $url = '';

        /**
         * Admin Menus
         *
         * @since 3.0
         * @var array
         */
        public $menus = array();

        /**
         * AJAX Controllers
         *
         * @since 3.0
         * @var array
         */
        public $controllers = array();

        /**
         * Form Fields
         *
         * @since 3.0
         * @var array
         */
        public $fields = array();

        /**
         * Form Actions
         *
         * @since 3.0
         * @var array
         */
        public $actions = array();

        /**
         * Merge Tags
         *
         * @since 3.0
         * @var array
         */
        public $merge_tags = array();

        /**
         * Model Factory
         *
         * @var object
         */
        public $factory = '';

        /**
         * Logger
         *
         * @var string
         */
        protected $_logger = '';

        /**
         * @var NF_Session
         */
        protected $session = '';

        /**
         * @var NF_Tracking
         */
        public $tracking;

        /**
         * Plugin Settings
         *
         * @since 3.0
         * @var array
         */
        protected $settings = array();

        protected $requests = array();

        protected $processes = array();

        /**
         * Main Ninja_Forms Instance
         *
         * Insures that only one instance of Ninja_Forms exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         *
         * @since 2.7
         * @static
         * @staticvar array $instance
         * @return Ninja_Forms Highlander Instance
         */
        public static function instance()
        {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Ninja_Forms ) ) {
                self::$instance = new Ninja_Forms;

                self::$dir = plugin_dir_path( __FILE__ );

                // Define old constants for backwards compatibility.
                if( ! defined( 'NF_PLUGIN_DIR' ) ){
                    define( 'NF_PLUGIN_DIR', self::$dir );
                    define( 'NINJA_FORMS_DIR', self::$dir . 'deprecated' );
                }

                self::$url = plugin_dir_url( __FILE__ );
                if( ! defined( 'NF_PLUGIN_URL' ) ){
                    define( 'NF_PLUGIN_URL', self::$url );
                }

                update_option( 'ninja_forms_version', self::VERSION );

                /*
                 * Register our autoloader
                 */
                spl_autoload_register( array( self::$instance, 'autoloader' ) );

                /*
                 * Admin Menus
                 */
                self::$instance->menus[ 'forms' ]           = new NF_Admin_Menus_Forms();
                self::$instance->menus[ 'all-forms' ]       = new NF_Admin_Menus_AllForms();
                self::$instance->menus[ 'add-new' ]         = new NF_Admin_Menus_AddNew();
                self::$instance->menus[ 'submissions']      = new NF_Admin_Menus_Submissions();
                self::$instance->menus[ 'import-export']    = new NF_Admin_Menus_ImportExport();
                self::$instance->menus[ 'settings' ]        = new NF_Admin_Menus_Settings();
                self::$instance->menus[ 'licenses']         = new NF_Admin_Menus_Licenses();
                self::$instance->menus[ 'system_status']    = new NF_Admin_Menus_SystemStatus();
                self::$instance->menus[ 'add-ons' ]         = new NF_Admin_Menus_Addons();
                self::$instance->menus[ 'divider']          = new NF_Admin_Menus_Divider();
                self::$instance->menus[ 'mock-data']        = new NF_Admin_Menus_MockData();

                /*
                 * AJAX Controllers
                 */
                self::$instance->controllers[ 'form' ]        = new NF_AJAX_Controllers_Form();
                self::$instance->controllers[ 'preview' ]     = new NF_AJAX_Controllers_Preview();
                self::$instance->controllers[ 'submission' ]  = new NF_AJAX_Controllers_Submission();
                self::$instance->controllers[ 'savedfields' ] = new NF_AJAX_Controllers_SavedFields();

                /*
                 * Async Requests
                 */
                require_once Ninja_Forms::$dir . 'includes/Libraries/BackgroundProcessing/classes/wp-async-request.php';
                self::$instance->requests[ 'delete-field' ] = new NF_AJAX_Requests_DeleteField();

                /*
                 * Background Processes
                 */
                require_once Ninja_Forms::$dir . 'includes/Libraries/BackgroundProcessing/wp-background-processing.php';
                self::$instance->requests[ 'update-fields' ] = new NF_AJAX_Processes_UpdateFields();

                /*
                 * WP-CLI Commands
                 */
                if( class_exists( 'WP_CLI_Command' ) ) {
                    WP_CLI::add_command('ninja-forms', 'NF_WPCLI_NinjaFormsCommand');
                }

                /*
                 * Preview Page
                 */
                self::$instance->preview = new NF_Display_Preview();

                /*
                 * Shortcodes
                 */
                self::$instance->shortcodes = new NF_Display_Shortcodes();

                /*
                 * Submission CPT
                 */
                new NF_Admin_CPT_Submission();
                new NF_Admin_CPT_DownloadAllSubmissions();
                require_once Ninja_Forms::$dir . 'lib/StepProcessing/menu.php';

                /*
                 * Logger
                 */
                self::$instance->_logger = new NF_Database_Logger();

                /*
                 * Merge Tags
                 */
                self::$instance->merge_tags[ 'user' ] = new NF_MergeTags_User();
                self::$instance->merge_tags[ 'post' ] = new NF_MergeTags_Post();
                self::$instance->merge_tags[ 'system' ] = new NF_MergeTags_System();
                self::$instance->merge_tags[ 'fields' ] = new NF_MergeTags_Fields();
                self::$instance->merge_tags[ 'calcs' ] = new NF_MergeTags_Calcs();
                self::$instance->merge_tags[ 'querystrings' ] = new NF_MergeTags_QueryStrings();
                self::$instance->merge_tags[ 'form' ] = new NF_MergeTags_Form();

                /*
                 * Add Form Modal
                 */
                self::$instance->add_form_modal = new NF_Admin_AddFormModal();

                /*
                 * EOS Parser
                 */
                self::$instance->_eos[ 'parser' ] = require_once 'includes/Libraries/EOS/Parser.php';

                self::$instance->session = new NF_Session();

                /*
                 * Plugin Settings
                 */
                self::$instance->settings = apply_filters( 'ninja_forms_settings', get_option( 'ninja_forms_settings' ) );

                /*
                 * Admin Notices System
                 */
                self::$instance->notices = new NF_Admin_Notices();

                self::$instance->widgets[] = new NF_Widget();

                /*
                 * Opt-In Tracking
                 */
                self::$instance->tracking = new NF_Tracking();

                /*
                 * Activation Hook
                 * TODO: Move to a permanent home.
                 */
                register_activation_hook( __FILE__, array( self::$instance, 'activation' ) );

                new NF_Admin_Metaboxes_AppendAForm();

                /*
                 * Require EDD auto-update file
                 */
                if( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
                    // Load our custom updater if it doesn't already exist
                    require_once( self::$dir . 'includes/Integrations/EDD/EDD_SL_Plugin_Updater.php');
                }
                require_once self::$dir . 'includes/Integrations/EDD/class-extension-updater.php';
            }

            add_action( 'admin_notices', array( self::$instance, 'admin_notices' ) );

            add_action( 'plugins_loaded', array( self::$instance, 'plugins_loaded' ) );

            add_action( 'ninja_forms_available_actions', array( self::$instance, 'scrub_available_actions' ) );

            add_action( 'init', array( self::$instance, 'init' ), 5 );
            add_action( 'admin_init', array( self::$instance, 'admin_init' ), 5 );

            return self::$instance;
        }

        public function init()
        {
            do_action( 'nf_init', self::$instance );
        }

        public function admin_init()
        {
            do_action( 'nf_admin_init', self::$instance );
        }

        public function scrub_available_actions( $actions )
        {
            foreach( $actions as $key => $action ){
                if ( ! is_plugin_active( $action[ 'plugin_path' ] ) )  continue;
                unset( $actions[ $key ] );
            }
            return $actions;
        }

        public function admin_notices()
        {
            // Notices filter and run the notices function.
            $admin_notices = Ninja_Forms()->config( 'AdminNotices' );
            self::$instance->notices->admin_notice( apply_filters( 'nf_admin_notices', $admin_notices ) );
        }

        public function plugins_loaded()
        {
            load_plugin_textdomain( 'ninja-forms', false, basename( dirname( __FILE__ ) ) . '/lang' );

            /*
             * Field Class Registration
             */
            self::$instance->fields = apply_filters( 'ninja_forms_register_fields', self::load_classes( 'Fields' ) );

            if( ! apply_filters( 'ninja_forms_enable_credit_card_fields', false ) ){
                unset( self::$instance->fields[ 'creditcard' ] );
                unset( self::$instance->fields[ 'creditcardcvc' ] );
                unset( self::$instance->fields[ 'creditcardexpiration' ] );
                unset( self::$instance->fields[ 'creditcardfullname' ] );
                unset( self::$instance->fields[ 'creditcardnumber' ] );
                unset( self::$instance->fields[ 'creditcardzip' ] );
            }

            /*
             * Form Action Registration
             */
            self::$instance->actions = apply_filters( 'ninja_forms_register_actions', self::load_classes( 'Actions' ) );

            /*
             * Merge Tag Registration
             */
            self::$instance->merge_tags = apply_filters( 'ninja_forms_register_merge_tags', self::$instance->merge_tags );

            /*
             * It's Ninja Time: Hook for Extensions
             */
            do_action( 'ninja_forms_loaded' );

        }

        /**
         * Autoloader
         *
         * Autoload Ninja Forms classes
         *
         * @param $class_name
         */
        public function autoloader( $class_name )
        {
            if( class_exists( $class_name ) ) return;

            /* Ninja Forms Prefix */
            if (false !== strpos($class_name, 'NF_')) {
                $class_name = str_replace('NF_', '', $class_name);
                $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
                $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
                if (file_exists($classes_dir . $class_file)) {
                    require_once $classes_dir . $class_file;
                }
            }

            /* WP Ninjas Prefix */
            if (false !== strpos($class_name, 'WPN_')) {
                $class_name = str_replace('WPN_', '', $class_name);
                $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
                $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
                if (file_exists($classes_dir . $class_file)) {
                    require_once $classes_dir . $class_file;
                }
            }
        }

        /*
         * PUBLIC API WRAPPERS
         */

        /**
         * Form Model Factory Wrapper
         *
         * @param $id
         * @return NF_Abstracts_ModelFactory
         */
        public function form( $id = '' )
        {
            global $wpdb;

            return new NF_Abstracts_ModelFactory( $wpdb, $id );
        }

        /**
         * Logger Class Wrapper
         *
         * Example Use:
         * Ninja_Forms()->logger()->log( 'debug', "Hello, {name}!", array( 'name' => 'world' ) );
         * Ninja_Forms()->logger()->debug( "Hello, {name}!", array( 'name' => 'world' ) );
         *
         * @return string
         */
        public function logger()
        {
            return $this->_logger;
        }

        public function eos()
        {
            return new Parser();
        }

        public function session()
        {
            return $this->session;
        }

        public function request( $action )
        {
            if( ! isset( $this->requests[ $action ] ) ) return new NF_AJAX_Requests_NullRequest();

            return $this->requests[ $action ];
        }

        public function background_process( $action )
        {
            if( ! isset( $this->requests[ $action ] ) ) return new NF_AJAX_Processes_NullProcess();

            return $this->requests[ $action ];
        }

	    /**
	     * Get a setting
	     *
	     * @param string     $key
	     * @param bool|false $default
	     * @return bool
	     */
        public function get_setting( $key = '', $default = false )
        {
            if( empty( $key ) || ! isset( $this->settings[ $key ] ) ) return $default;

            return $this->settings[ $key ];
        }

	    /**
	     * Get all the settings
	     *
	     * @return array
	     */
        public function get_settings()
        {
            return ( is_array( $this->settings ) ) ? $this->settings : array();
        }

	    /**
	     * Update a setting
	     *
	     * @param string           $key
	     * @param mixed           $value
	     * @param bool|false $defer_update Defer the database update of all settings
	     */
        public function update_setting( $key, $value, $defer_update = false )
        {
	        $this->settings[ $key ] = $value;
	        if ( ! $defer_update ) {
		        $this->update_settings();
	        }
        }

	    /**
	     * Save settings to database
	     *
	     * @param array $settings
	     */
        public function update_settings( $settings = array() )
        {
            if( ! is_array( $this->settings ) ) $this->settings = array();

            if( $settings && is_array( $settings ) ) {
                $this->settings = array_merge($this->settings, $settings);
            }

            update_option( 'ninja_forms_settings', $this->settings );
        }


        /**
         * Display Wrapper
         *
         * @param $form_id
         */
        public function display( $form_id, $preview = FALSE )
        {
            if( ! $form_id ) return;

            $noscript_message = __( 'Notice: JavaScript is required for this content.', 'ninja-forms' );
            $noscript_message = apply_filters( 'ninja_forms_noscript_message', $noscript_message );

            Ninja_Forms()->template( 'display-noscript-message.html.php', array( 'message' => $noscript_message ) );

            if( ! $preview ) {
                NF_Display_Render::localize($form_id);
            } else {
                NF_Display_Render::localize_preview($form_id);
            }
        }



        /*
         * PRIVATE METHODS
         */

        /**
         * Load Classes from Directory
         *
         * @param string $prefix
         * @return array
         */
        private static function load_classes( $prefix = '' )
        {
            $return = array();

            $subdirectory = str_replace( '_', DIRECTORY_SEPARATOR, str_replace( 'NF_', '', $prefix ) );

            $directory = 'includes/' . $subdirectory;

            foreach (scandir( self::$dir . $directory ) as $path) {

                $path = explode( DIRECTORY_SEPARATOR, str_replace( self::$dir, '', $path ) );
                $filename = str_replace( '.php', '', end( $path ) );

                $class_name = 'NF_' . $prefix . '_' . $filename;

                if( ! class_exists( $class_name ) ) continue;

                $return[ strtolower( $filename ) ] = new $class_name;
            }

            return $return;
        }



        /*
         * STATIC METHODS
         */

        /**
         * Template
         *
         * @param string $file_name
         * @param array $data
         */
        public static function template( $file_name = '', array $data = array(), $return = FALSE )
        {
            if( ! $file_name ) return FALSE;

            extract( $data );

            $path = self::$dir . 'includes/Templates/' . $file_name;

            if( ! file_exists( $path ) ) return FALSE;

            if( $return ) return file_get_contents( $path );

            include $path;
        }

        /**
         * Config
         *
         * @param $file_name
         * @return mixed
         */
        public static function config( $file_name )
        {
            return include self::$dir . 'includes/Config/' . $file_name . '.php';
        }

        /**
         * Activation
         */
        public function activation() {
            $migrations = new NF_Database_Migrations();
            $migrations->migrate();

            if( Ninja_Forms()->form()->get_forms() ) return;

            $form = Ninja_Forms::template( 'formtemplate-contactform.nff', array(), TRUE );
            Ninja_Forms()->form()->import_form( $form );
        }

        /**
         * Deprecated Notice
         *
         * Example: Ninja_Forms::deprecated_hook( 'ninja_forms_old', '3.0', 'ninja_forms_new', debug_backtrace() );
         *
         * @param $deprecated
         * @param $version
         * @param null $replacement
         * @param null $backtrace
         */
        public static function deprecated_notice( $deprecated, $version, $replacement = null, $backtrace = null )
        {
            do_action( 'ninja_forms_deprecated_call', $deprecated, $replacement, $version );

            $show_errors = current_user_can( 'manage_options' );

            // Allow plugin to filter the output error trigger
            if ( WP_DEBUG && apply_filters( 'ninja_forms_deprecated_function_trigger_error', $show_errors ) ) {
                if ( ! is_null( $replacement ) ) {
                    trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Ninja Forms version %2$s! Use %3$s instead.', 'ninja-forms' ), $deprecated, $version, $replacement ) );
                    // trigger_error(  print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
                    // Alternatively we could dump this to a file.
                } else {
                    trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Ninja Forms version %2$s.', 'ninja-forms' ), $deprecated, $version ) );
                    // trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
                    // Alternatively we could dump this to a file.
                }
            }
        }

    } // End Class Ninja_Forms



    /**
     * The main function responsible for returning The Highlander Ninja_Forms
     * Instance to functions everywhere.
     *
     * Use this function like you would a global variable, except without needing
     * to declare the global.
     *
     * Example: <?php $nf = Ninja_Forms(); ?>
     *
     * @since 2.7
     * @return Ninja_Forms Highlander Instance
     */
    function Ninja_Forms()
    {
        return Ninja_Forms::instance();
    }

    Ninja_Forms();

    /*
    |--------------------------------------------------------------------------
    | Uninstall Hook
    |--------------------------------------------------------------------------
    */

    register_uninstall_hook( __FILE__, 'ninja_forms_uninstall' );

    function ninja_forms_uninstall(){

        if( Ninja_Forms()->get_setting( 'delete_on_uninstall ') ) {
            require_once plugin_dir_path(__FILE__) . '/includes/Database/Migrations.php';
            $migrations = new NF_Database_Migrations();
            $migrations->nuke(TRUE, TRUE);
        }
    }

    // Scheduled Action Hook
    function nf_optin_send_admin_email( ) {
        /*
         * If we aren't opted in, or we've specifically opted out, then return false.
         */
        if ( ! Ninja_Forms()->tracking->is_opted_in() || Ninja_Forms()->tracking->is_opted_out() ) {
            return false;
        }

        /*
         * If we haven't already submitted our email to api.ninjaforms.com, submit it and set an option saying we have.
         */
        
        if ( get_option ( 'ninja_forms_optin_admin_email', false ) ) {
            return false;
        }

        /*
         * Ping api.ninjaforms.com
         */
        
        $admin_email = get_option('admin_email');
        $url = home_url();
        $response = wp_remote_post(
            'http://api.ninjaforms.com',
            array(
                'body' => array( 'admin_email' => $admin_email, 'url' => $url ),
            )
        );

        if( is_array($response) ) {
            $header = $response['headers']; // array of http header lines
            $body = $response['body']; // use the content
        }

        update_option( 'ninja_forms_optin_admin_email', true );
    }

    add_action( 'nf_optin_cron', 'nf_optin_send_admin_email' );

    // Custom Cron Recurrences
    function nf_custom_cron_job_recurrence( $schedules ) {
        $schedules['nf-monthly'] = array(
            'display' => __( 'Once per month', 'textdomain' ),
            'interval' => 2678400,
        );
        return $schedules;
    }
    add_filter( 'cron_schedules', 'nf_custom_cron_job_recurrence' );

    // Schedule Cron Job Event
    function nf_optin_send_admin_email_cron_job() {
        if ( ! wp_next_scheduled( 'nf_optin_cron' ) ) {
            nf_optin_send_admin_email();
            wp_schedule_event( current_time( 'timestamp' ), 'nf-monthly', 'nf_optin_cron' );
        }
    }

    add_action( 'wp', 'nf_optin_send_admin_email_cron_job' );
}