<?php

namespace Gravity_Forms\Gravity_SMTP\Telemetry;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_Tools\Logging\Logger;
use Gravity_Forms\Gravity_Tools\Telemetry\Telemetry_Data;
use Gravity_Forms\Gravity_Tools\Utils\Common;

class Telemetry_Snapshot_Data extends Telemetry_Data {

	const METRIC_MIGRATIONS_RUN = 'migrations_used';

	/**
	 * @var string $key Identifier for this data object.
	 */
	public $key = 'snapshot';

	public $enabled_setting_name = Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS;

	public $data_setting_name = 'gravitysmtp_usage_analytics';

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var Event_Model
	 */
	protected $events;

	/**
	 * @var Data_Store_Router
	 */
	protected $plugin_settings;

	public function __construct( Common $common, Logger $logger, Event_Model $events, Data_Store_Router $plugin_settings ) {
		$this->common          = $common;
		$this->logger          = $logger;
		$this->events          = $events;
		$this->plugin_settings = $plugin_settings;
	}

	public function is_data_collection_allowed() {
		static $is_allowed;

		if ( ! is_null( $is_allowed ) ) {
			return $is_allowed;
		}

		$is_allowed = $this->plugin_settings->get_plugin_setting( $this->enabled_setting_name, true );

		return $is_allowed;
	}

	public function record_data() {
		/**
		 * Array of callback functions returning an array of data to be included in the telemetry snapshot.
		 *
		 * @since 1.0
		 */
		$callbacks = array(
			array( $this, 'get_site_basic_info' ),
			array( $this, 'get_event_counts' ),
			array( $this, 'get_gsmtp_config' ),
		);

		// Merges the default callbacks with any additional callbacks added via the gform_telemetry_snapshot_data filter. Default callbacks are added last so they can't be overridden.
		$callbacks = array_merge( $this->get_callbacks(), $callbacks );

		$data = array();

		foreach ( $callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				$data = array_merge( $data, call_user_func( $callback ) );
			}
		}

		$this->save_data( $data );
	}

	/**
	 * Get additional callbacks that return data to be included in the telemetry snapshot.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_callbacks() {
		/**
		 * Filters the non-default data to be included in the telemetry snapshot.
		 *
		 * @since 1.0
		 *
		 * @param array $new_callbacks An array of callbacks returning an array of data to be included in the telemetry snapshot. Default empty array.
		 */
		return apply_filters( 'gravitysmtp_telemetry_snapshot_data', array() );
	}

	/**
	 * Get basic site info for telemetry.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_site_basic_info() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_list = get_plugins();
		$plugins     = array();

		$active_plugins = get_option( 'active_plugins' );

		foreach ( $plugin_list as $key => $plugin ) {
			$is_active = in_array( $key, $active_plugins );

			$slug = substr( $key, 0, strpos( $key, '/' ) );
			if ( empty( $slug ) ) {
				$slug = str_replace( '.php', '', $key );
			}

			$plugins[] = array(
				'name'      => str_replace( 'phpinfo()', 'PHP Info', $plugin['Name'] ),
				'slug'      => $slug,
				'version'   => $plugin['Version'],
				'is_active' => $is_active,
			);
		}

		$theme            = wp_get_theme();
		$theme_name       = $theme->get( 'Name' );
		$theme_uri        = $theme->get( 'ThemeURI' );
		$theme_version    = $theme->get( 'Version' );
		$theme_author     = $theme->get( 'Author' );
		$theme_author_uri = $theme->get( 'AuthorURI' );

		$im   = is_multisite();
		$lang = get_locale();
		$db   = Common::get_dbms_type();

		$post = array(
			'of'      => 'gravitysmtp',
			'key'     => $this->common->get_key(),
			'wp'      => get_bloginfo( 'version' ),
			'php'     => phpversion(),
			'mysql'   => Common::get_db_version(),
			'plugins' => $plugins,
			'tn'      => $theme_name,
			'tu'      => $theme_uri,
			'tv'      => $theme_version,
			'ta'      => $theme_author,
			'tau'     => $theme_author_uri,
			'im'      => $im,
			'lang'    => $lang,
			'db'      => $db,
		);

		return $post;
	}

	/**
	 * Collect data regarding event counts.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_event_counts() {
		if ( ! $this->is_data_collection_allowed() ) {
			return array();
		}

		return array( 'gravitysmtp_event_counts' => $this->events->get_counts() );
	}

	public function get_gsmtp_config() {
		if ( ! $this->is_data_collection_allowed() ) {
			return array();
		}

		return array(
			'gravitysmtp_config' => array(
				Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE              => $this->plugin_settings->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE, false ),
				Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED      => $this->plugin_settings->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, true ),
				Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION    => $this->plugin_settings->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION, 180 ),
				Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS        => $this->plugin_settings->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS, true ),
				Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR => $this->plugin_settings->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR ),
				self::METRIC_MIGRATIONS_RUN => $this->plugin_settings->get_plugin_setting( self::METRIC_MIGRATIONS_RUN, array() ),
			),
		);
	}

	/**
	 * Stores the response from the version.php endpoint, to be used by the license service.
	 *
	 * @since 1.0
	 *
	 * @param array $response Raw response from the API endpoint.
	 */
	public function after_send( $response ) {
		$version_info = array(
			'is_valid_key' => '1',
			'version'      => '',
			'url'          => '',
			'is_error'     => '1',
		);

		if ( is_wp_error( $response ) || $this->common->rgars( $response, 'response/code' ) != 200 ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded = json_decode( $response['body'], true );

		if ( empty( $decoded ) ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded['timestamp'] = time();

		update_option( 'gsmtp_version_info', $decoded, false );

		$this->logger->log_debug( __METHOD__ . sprintf( '(): Version info cached.' ) );
	}
}
