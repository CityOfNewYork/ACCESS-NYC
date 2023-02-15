<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://bitly.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.6.0
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 * @author     Bitly
 */
class Wp_Bitly {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.6.0
	 * @access   protected
	 * @var      Wp_Bitly_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		if ( defined( 'WPBITLY_VERSION' ) ) {
			$this->version = WPBITLY_VERSION;
		} else {
			$this->version = '2.6.0';
		}
		$this->plugin_name = 'wp-bitly';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Bitly_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Bitly_i18n. Defines internationalization functionality.
	 * - Wp_Bitly_Admin. Defines all hooks for the admin area.
	 * - Wp_Bitly_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.6.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-i18n.php';

		/**
		 * The class responsible for authorizing with Bitly and interacting with the Bitly API.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-auth.php';

		/**
		 * The class responsible for managing options for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-options.php';

		/**
		 * The class responsible for managing settings for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-settings.php';

		/**
		 * The class responsible for logging for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-logger.php';

		/**
		 * The class responsible for showing the metabox for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-metabox.php';

		/**
		 * The class responsible for managing shortlinks and the shortcode for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-shortlink.php';

		/**
		 * The class responsible for interacting with the Bitly API.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-bitly-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area for the WP Bitly plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-bitly-admin.php';

	

		$this->loader = new Wp_Bitly_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Bitly_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.6.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Bitly_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    2.6.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Bitly_Admin($this->get_plugin_name(), $this->get_version());
		$plugin_settings = new Wp_Bitly_Settings();
		$plugin_auth = new Wp_Bitly_Auth();
		$plugin_metabox = new Wp_Bitly_Metabox();
		$plugin_shortlink = new Wp_Bitly_Shortlink();
		$plugin_api = new Wp_Bitly_Api();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	

		$this->loader->add_action( 'init', $plugin_admin, 'check_for_authorization' );
		$this->loader->add_action( 'init', $plugin_admin, 'regenerate_links' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );
		$this->loader->add_action( 'wp_ajax_get_domain_options', $plugin_settings, 'get_domain_options' );
                $this->loader->add_action( 'wp_ajax_get_group_options', $plugin_settings, 'get_group_options' );
                $this->loader->add_action( 'wp_ajax_get_org_options', $plugin_settings, 'get_org_options' );
		
		//these functions are used to automatically get the shortlink for a post on save and in the shortcode
		$this->loader->add_action('save_post',$plugin_shortlink,'wpbitly_get_shortlink', 20, 2); //this was removed in prior versions, but reintroduced here, pre_get_shortlink was not firing
		$this->loader->add_filter('pre_get_shortlink',$plugin_shortlink,'wpbitly_get_shortlink', 20, 2);

		//register shortcode
		$this->loader->add_action( 'init', $plugin_shortlink, 'wpbitly_register_shortlink' );

		$this->loader->add_action( 'admin_init', $plugin_metabox, 'register_metaboxes' );

		$this->loader->add_filter('plugin_action_links_' . plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ), $plugin_admin, 'add_action_links');

	}



	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.6.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.6.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.6.0
	 * @return    Wp_Bitly_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.6.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}