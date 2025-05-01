<?php
/**
 * /premium/class-relevanssi-wp-auto-update.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Manages the auto update system for Relevanssi Premium.
 *
 * Manages the auto updates, getting update information from Relevanssi.com and passing it to the WordPress update system.
 */
class Relevanssi_WP_Auto_Update {
	/**
	 * The plugin current version
	 *
	 * @var string
	 */
	public $current_version;

	/**
	 * The plugin remote update path
	 *
	 * @var string
	 */
	public $update_path;

	/**
	 * Plugin Slug (plugin_directory/plugin_file.php)
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Plugin name (plugin_file)
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Initializes a new instance of the WordPress Auto-Update class
	 *
	 * @param string $current_version Current version of the plugin.
	 * @param string $update_path Path to the remove service.
	 * @param string $plugin_slug Plugin slug.
	 */
	public function __construct( $current_version, $update_path, $plugin_slug ) {
		// Set the class public variables.
		$this->current_version = $current_version;
		$this->update_path     = $update_path;
		$this->plugin_slug     = $plugin_slug;
		list ($t1, $t2)        = explode( '/', $plugin_slug );
		$this->slug            = str_replace( '.php', '', $t2 );
		if ( 'on' !== get_option( 'relevanssi_do_not_call_home' ) ) {
			// define the alternative API for updating checking.
			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );

			// Define the alternative response for information checking.
			add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
		}
	}

	/**
	 * Adds our self-hosted autoupdate plugin to the filter transient.
	 *
	 * @param object $transient The filtered transient.
	 *
	 * @return object $transient
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		// Get the remote version.
		$info           = $this->get_remote_information();
		$remote_version = 0;
		if ( isset( $info->new_version ) ) {
			$remote_version = $info->new_version;
		}

		// If a newer version is available, add the update.
		if ( version_compare( $this->current_version, $remote_version, '<' ) ) {
			$obj              = new stdClass();
			$obj->slug        = $this->slug;
			$obj->new_version = $remote_version;
			$obj->url         = $info->package;
			$obj->package     = $info->package;
			$obj->icons       = $info->icons;
			$obj->banners     = $info->banners;

			$transient->response[ $this->plugin_slug ] = $obj;
		} else {
			global $relevanssi_variables;
			// No update is available.
			$item = (object) array(
				'id'            => 'relevanssi-premium/relevanssi.php',
				'slug'          => 'relevanssi-premium',
				'plugin'        => 'relevanssi-premium/relevanssi.php',
				'new_version'   => $relevanssi_variables['plugin_version'],
				'url'           => '',
				'package'       => '',
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '',
				'requires_php'  => '',
				'compatibility' => new stdClass(),
			);
			$transient->no_update['relevanssi-premium/relevanssi.php'] = $item;
		}

		return $transient;
	}

	/**
	 * Adds our self-hosted description to the filter.
	 *
	 * @param object $api    Result object or array, should return false.
	 * @param array  $action Type of information request.
	 * @param object $args   Plugin API arguments.
	 *
	 * @return object $api   New stdClass with plugin information on success, default response on failure.
	 */
	public function check_info( $api, $action, $args ) {
		$plugin = ( 'plugin_information' === $action ) && isset( $args->slug ) && ( $this->slug === $args->slug );

		if ( $plugin ) {
			if ( $args->slug === $this->slug ) {
				$information = $this->get_remote_information();
				return $information;
			}
		}
		return $api;
	}

	/**
	 * Returns the remote version.
	 *
	 * @return string $remote_version Version number at the remote end.
	 */
	public function get_remote_version() {
		$api_key = get_network_option( null, 'relevanssi_api_key' );
		if ( ! $api_key ) {
			$api_key = get_option( 'relevanssi_api_key' );
		}
		$request = wp_remote_post(
			$this->update_path,
			array(
				'body' => array(
					'api_key' => $api_key,
					'action'  => 'version',
				),
			)
		);
		if ( ! is_wp_error( $request ) || 200 === wp_remote_retrieve_response_code( $request ) ) {
			return $request['body'];
		}
		return false;
	}

	/**
	 * Get information about the remote version.
	 *
	 * @return bool|object
	 */
	public function get_remote_information() {
		$api_key = get_network_option( null, 'relevanssi_api_key' );
		if ( ! $api_key ) {
			$api_key = get_option( 'relevanssi_api_key' );
		}
		$request = wp_remote_post(
			$this->update_path,
			array(
				'body' => array(
					'api_key' => $api_key,
					'action'  => 'info',
				),
			)
		);
		if ( ! is_wp_error( $request ) || 200 === wp_remote_retrieve_response_code( $request ) ) {
			if ( is_serialized( $request['body'] ) ) {
				return unserialize( $request['body'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			}
		}
		return false;
	}

	/**
	 * Returns the status of the plugin licensing.
	 *
	 * @return boolean $remote_license
	 */
	public function get_remote_license() {
		$api_key = get_network_option( null, 'relevanssi_api_key' );
		if ( ! $api_key ) {
			$api_key = get_option( 'relevanssi_api_key' );
		}
		$request = wp_remote_post(
			$this->update_path,
			array(
				'body' => array(
					'api_key' => $api_key,
					'action'  => 'license',
				),
			)
		);
		if ( ! is_wp_error( $request ) || 200 === wp_remote_retrieve_response_code( $request ) ) {
			if ( 'false' === $request['body'] ) {
				return false;
			}
			return $request['body'];
		}
		return false;
	}
}
