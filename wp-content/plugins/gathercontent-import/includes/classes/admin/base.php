<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\API;
use GatherContent\Importer\Settings\Setting;

abstract class Base extends Enqueue {

	const SLUG = GATHERCONTENT_SLUG;
	public $option_name        = '';
	public $option_group       = '';
	public $url                = '';
	public $step               = 0;
	public $menu_priority      = 9;

	/**
	 * The account id.
	 *
	 * @var null|object
	 */
	public $account = null;

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected static $api = null;

	/**
	 * GatherContent\Importer\Settings instance
	 *
	 * @var GatherContent\Importer\Settings
	 */
	protected $settings = null;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param $api API object
	 */
	public function __construct() {
		$this->url = admin_url( 'admin.php?page='. static::SLUG );
		$this->logo = '<img width="220px" height="39px" src="'. GATHERCONTENT_URL . 'images/logo.svg" alt="GatherContent" />';
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		$callbacks = array(
			'admin_menu' => 'admin_menu',
			'admin_init' => 'initialize_settings_sections',
		);

		foreach ( $callbacks as $hook => $cb ) {
			if ( did_action( $hook ) ) {
				$this->{$cb}();
			} else {
				add_action( $hook, array( $this, $cb ), $this->menu_priority );
			}
		}
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	abstract public function admin_menu();

	abstract public function admin_page();

	/**
	 * Initializes the plugin's setting, and settings sections/Fields.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function initialize_settings_sections() {
		register_setting(
			$this->option_group,
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	public function sanitize_settings( $options ) {
		self::$api->flush_cache();
		return $this->settings()->sanitize_settings( $options );
	}

	/**
	 * `add_settings_error` wrapper which is useable before `add_settings_error` is.
	 *
	 * @since NEXT
	 *
	 * @global array $wp_settings_errors Storage array of errors registered during this pageload
	 *
	 * @param string $setting Slug title of the setting to which this error applies
	 * @param string $code    Slug-name to identify the error. Used as part of 'id' attribute in HTML output.
	 * @param string $message The formatted message text to display to the user (will be shown inside styled
	 *                        `<div>` and `<p>` tags).
	 * @param string $type    Optional. Message type, controls HTML class. Accepts 'error' or 'updated'.
	 *                        Default 'error'.
	 */
	protected function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		if ( function_exists( 'add_settings_error' ) ) {
			return add_settings_error( $setting, $code, $message, $type );
		}

		global $wp_settings_errors;
		$wp_settings_errors = is_array( $wp_settings_errors ) ? $wp_settings_errors : array();

		// because it's too early to use add_settings_error.
		$wp_settings_errors[] = array(
			'setting' => $setting,
			'code'    => $code,
			'message' => $message,
			'type'    => $type
		);
	}

	/**
	 * Determine which step user is on.
	 *
	 * @todo  This should be determined which options they have filled out, and redirect user to step.
	 *
	 * @since  3.0.0
	 *
	 * @return int  Step number.
	 */
	public function which_step() {
		return $this->step;
	}

	/**
	 * Get option value.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key Key from options array to retrieve.
	 *
	 * @return mixed       Value for option.
	 */
	public function get_setting( $key ) {
		return $this->settings()->get( $key );
	}

	/**
	 * Gets the Settings object
	 *
	 * @since  3.0.0
	 *
	 * @return Settings
	 */
	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = new Setting( $this->option_name, $this->default_options );
		}

		return $this->settings;
	}

	protected function api() {
		if ( null === self::$api ) {
			throw new \Exception( 'Must set the API object with '. get_class( $this ) .'::set_api( $api ).' );
		}

		return self::$api;
	}

	protected function set_api( API $api ) {
		return self::$api = $api;
	}

	/**
	 * Uses the platform URL to determine which account in the accounts object
	 * (from the API) to set as the account.
	 *
	 * @since 3.0.0
	 *
	 * @return  bool Whether account was successfully set.
	 */
	public function set_my_account() {
		$accounts = $this->api()->get_accounts();
		$account_slug = $this->get_setting( 'platform_url_slug' );

		if ( ! $accounts || ! $account_slug ) {
			return false;
		}

		foreach ( $accounts as $index => $account ) {
			if ( $account_slug === $account->slug ) {
				$this->account = $account;
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the platform URL using the saved account slug.
	 *
	 * @since  3.0.0
	 *
	 * @param string $path Optionally append a path to the platform URL.
	 *
	 * @return string  The account platform url.
	 */
	public function platform_url( $path = '' ) {
		return 'https://'. $this->_get_account_slug() .'.gathercontent.com/' . $path;
	}

	protected function _get_account_slug() {
		$slug = $this->get_setting( 'platform_url_slug' );

		if ( $project = $this->_get_val( 'project' ) ) {
			$parts = explode( ':', $project );
			if ( isset( $parts[1] ) ) {
				$slug = sanitize_text_field( $parts[1] );
			}
		}

		return $slug;
	}

	protected function _get_account_id() {
		$id = 0;
		if ( $project = $this->_get_val( 'project' ) ) {
			$parts = explode( ':', $project );
			if ( isset( $parts[2] ) ) {
				$id = absint( $parts[2] );
			}
		}

		return $id;
	}

}
