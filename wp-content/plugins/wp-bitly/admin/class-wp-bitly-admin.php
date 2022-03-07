<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/admin
 */

class Wp_Bitly_Admin {

    /**
     * The unique identifier of this plugin.
     *
     * @since    2.6.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    2.6.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The auth class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class  $wp_bitly_auth
     */
    protected $wp_bitly_auth;

    /**
     * The options class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_options;

    /**
     * The logger class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class wp_bitly_logger
     */
    protected $wp_bitly_logger;

    /**
     * The shortlink class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_shortlink
     */
    protected $wp_bitly_shortlink;

	/**
	 * Initialize 
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
        $this->$plugin_name = $plugin_name;
        $this->version = $version;

        $this->wp_bitly_auth = new Wp_Bitly_Auth(); 
        $this->wp_bitly_options = new Wp_Bitly_Options(); 
        $this->wp_bitly_logger = new Wp_Bitly_Logger(); 
        $this->wp_bitly_shortlink = new Wp_Bitly_Shortlink();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    2.6.0
	 */
	public function enqueue_styles() {

            wp_enqueue_style( $this->plugin_name.'-wp-bitly-admin-css', plugin_dir_url( __FILE__ ) . 'css/wp-bitly-admin.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name.'-chartist-min-css', plugin_dir_url( __FILE__ ) . 'css/chartist/chartist.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    2.6.0
	 */
	public function enqueue_scripts( $hook ) {

            if ( 'options-writing.php' == $hook ) {
                wp_enqueue_script( $this->plugin_name.'wp-bitly-admin-js', plugin_dir_url( __FILE__ ) . 'js/wp-bitly-admin.js', array( 'jquery' ), $this->version, false );
            }
            wp_enqueue_script( $this->plugin_name.'chartist-min-js', plugin_dir_url( __FILE__ ) . 'js/chartist/chartist.min.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * Add a settings link to the plugins page so people can figure out where we are.
     *
     * @since   2.0
     *
     * @param   $links An array returned by WordPress with our plugin action links
     *
     * @return  array The slightly modified 'rray.
     */
    public function add_action_links($links)
    {

        return array_merge(array('settings' => '<a href="' . admin_url('options-writing.php') . '">' . __('Settings', 'wp-bitly') . '</a>'), $links);

    }

    /**
     * Display a simple and unobtrusive notice on the plugins page after activation (and
     * up until the plugin is authorized with Bitly).
     *
     * @since   2.0
     */
    public function display_settings_notice()
    {
       
        $screen = get_current_screen();

        if ($screen->base != 'plugins' || $this->wp_bitly_auth->isAuthorized()) {
            return;
        }

        $prologue = __('WP Bitly is almost ready!', 'wp-bitly');
        $link = sprintf('<a href="%s">', admin_url('options-writing.php')) . __('settings page', 'wp-bitly') . '</a>';
        $epilogue = sprintf(__('Please visit the %s to configure WP Bitly', 'wp-bitly'), $link);

        $message = apply_filters('wpbitly_setup_notice', sprintf('<div id="message" class="updated"><p>%s %s</p></div>', $prologue, $epilogue));

        echo $message;

    }


    /**
     * Checks for authorization information from Bitly, alternatively disconnects the current authorization
     * by deleting the token.
     *
     * @since 2.4.1
     */
    public function check_for_authorization()
    {
 

        if (!$this->wp_bitly_auth->isAuthorized() && isset($_GET['access_token']) && isset($_GET['login'])) {



            $token = sanitize_text_field($_GET['access_token']);
            $login = sanitize_text_field($_GET['login']);

            $this->wp_bitly_logger->wpbitly_debug_log(array('Referer' => $_SERVER['HTTP_REFERER'], 'Query String' => $_SERVER['QUERY_STRING']), 'Authorizing Env');
            $this->wp_bitly_logger->wpbitly_debug_log(array('access_token' => $token, 'login' => $login, 'Escaped access_token' => esc_attr($token)), 'Authorizing');

            $this->wp_bitly_options->set_option('oauth_token', $token);
            $this->wp_bitly_options->set_option('oauth_login', $login);

            $this->wp_bitly_auth->authorize(true);

            add_action('admin_notices', array($this, 'authorization_successful_notice'));

        }

        if ($this->wp_bitly_auth->isAuthorized() && isset($_GET['disconnect']) && 'bitly' == $_GET['disconnect']) {
			
            $this->wp_bitly_logger->wpbitly_debug_log('', 'Disconnecting');
            $this->wp_bitly_options->set_option('oauth_token', '');
            $this->wp_bitly_options->set_option('oauth_login', '');

            $this->wp_bitly_auth->authorize(false);
        }

    }



	/**
     * Displays a notice at the top of the screen after a successful authorization
     *
     * @since 2.4.1
     */
    public function authorization_successful_notice()
    {
        $token = sanitize_text_field($_GET['access_token']);

        if ($this->wp_bitly_auth->isAuthorized()) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'wp-bitly') . '</strong> ' . __('WP Bitly is authorized, and you can start generating shortlinks!', 'wp-bitly') . '<br>';
            echo sprintf('Your access token is: <code>%s</code>', $token) . '</p></div>';
        }
    }

	/**
     * Displays a notice at the top of the screen after a successful shortlink regeneration
     *
     * @since 2.5.0
     */
    public function regenerate_successful_notice()
    {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'wp-bitly') . '</strong> ' . __('The shortlink for this post has been regenerated.', 'wp-bitly') . '</p></div>';
    }

    /**
     * Verifies the shortlink attached to the current post with Bitly, and regenerates the link upon failure.
     *
     * @uses wpbitly_generate_shortlink()
     * @since 2.5.0
     */
    public function regenerate_links()
    {

        if (isset($_GET['wpbr']) && isset($_GET['post'])) {

            if (!$this->wp_bitly_auth->isAuthorized() || !is_numeric($_GET['post'])) {
                return false;
            }

            $post_id = (int)sanitize_text_field($_GET['post']);
            
            $this->wp_bitly_shortlink->wpbitly_generate_shortlink($post_id, 1);

            add_action('admin_notices', array($this, 'regenerate_successful_notice'));

        }

    }
    

}
