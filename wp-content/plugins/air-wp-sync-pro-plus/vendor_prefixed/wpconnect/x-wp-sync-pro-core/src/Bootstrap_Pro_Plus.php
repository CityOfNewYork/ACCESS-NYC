<?php
/**
 * Main entry point for the Pro Plus plugin, initialize all features.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core;

use WP_CLI;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\ACF\ACF_Destination_Post;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\ACF\ACF_Destination_User;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\AIOSEO_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar\Events_Calendar_Destination_Event;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar\Events_Calendar_Destination_Organizer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar\Events_Calendar_Destination_Venue;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar\Formatters\Format_Event_Organiser_ID_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\RankMath_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\SEOPress_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Yoast_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Formatters\ACF_Format_To_URL_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Bootstrap_Pro_Plus class.
 */
class Bootstrap_Pro_Plus {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'airwpsync/init_services', array( $this, 'register_acf_services' ), 10, 1 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_acf_destination' ), 10, 0 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_yoast_destination' ), 10, 0 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_rankmath_destination' ), 10, 0 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_seopress_destination' ), 10, 0 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_aioseo_destination' ), 10, 0 );
		add_action( 'airwpsync/register_destination', array( $this, 'register_events_calendar_destination' ), 10, 0 );
	}

	/**
	 * Register ACF services.
	 *
	 * @param Services $services Dependency injection container.
	 *
	 * @return void
	 */
	public function register_acf_services( $services ) {
		$services->get( 'formatter' )
				->register_strategy( new ACF_Format_To_URL_Strategy() );
	}

	/**
	 * Init plugin.
	 */
	public function register_acf_destination() {
		if ( class_exists( 'ACF' ) ) {
			new ACF_Destination_Post( Services::get_instance()->get( 'formatter' ) );
			new ACF_Destination_User( Services::get_instance()->get( 'formatter' ) );
		}
	}

	/**
	 * Yoast SEO support
	 */
	public function register_yoast_destination() {
		if ( class_exists( 'WPSEO_Options' ) ) {
			new Yoast_Destination( Services::get_instance()->get( 'formatter' ) );
		}
	}

	/**
	 * RankMath SEO support
	 */
	public function register_rankmath_destination() {
		if ( class_exists( 'RankMath' ) ) {
			new RankMath_Destination( Services::get_instance()->get( 'formatter' ) );
		}
	}

	/**
	 * SEOPress support
	 */
	public function register_seopress_destination() {
		if ( class_exists( 'SEOPress\Core\Kernel' ) ) {
			new SEOPress_Destination( Services::get_instance()->get( 'formatter' ) );
		}
	}

	/**
	 * All in One SEO Module support instanciation
	 */
	public function register_aioseo_destination() {
		if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
			new AIOSEO_Destination( Services::get_instance()->get( 'formatter' ) );
		}
	}

	/**
	 * The Events Calendar support instantiation
	 */
	public function register_events_calendar_destination() {
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$services = Services::get_instance();
			$services->get( 'formatter' )->register_strategy( new Format_Event_Organiser_ID_Strategy() );
			new Events_Calendar_Destination_Event( $services->get( 'formatter' ) );
			new Events_Calendar_Destination_Organizer( $services->get( 'formatter' ) );
			new Events_Calendar_Destination_Venue( $services->get( 'formatter' ) );
		}
	}
}
