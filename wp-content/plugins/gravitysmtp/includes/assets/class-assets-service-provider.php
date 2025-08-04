<?php

namespace Gravity_Forms\Gravity_SMTP\Assets;

use Gravity_Forms\Gravity_SMTP\Environment\Environment_Details;
use Gravity_Forms\Gravity_Tools\Assets\Asset_Processor;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Assets_Service_Provider extends Service_Provider {

	const ENVIRONMENT_DETAILS = 'environment_details';
	const HASH_MAP_JS         = 'hash_map_js';
	const HASH_MAP_CSS        = 'hash_map_css';
	const ASSET_PROCESSOR     = 'asset_processor';

	protected $plugin_url;

	protected $dev_plugin_url;

	protected $plugin_path;

	public function __construct( $plugin_url, $dev_plugin_url, $plugin_path ) {
		$this->plugin_url     = $plugin_url;
		$this->dev_plugin_url = $dev_plugin_url;
		$this->plugin_path    = $plugin_path;
	}

	public function register( Service_Container $container ) {
		$container->add( self::ENVIRONMENT_DETAILS, function () {
			return new Environment_Details();
		} );

		$container->add( self::HASH_MAP_JS, function () use ( $container ) {
			if ( ! file_exists( $this->plugin_path . 'assets/js/dist/assets.php' ) ) {
				return array();
			}

			$map = require( $this->plugin_path . 'assets/js/dist/assets.php' );

			$common = $container->get( Utils_Service_Provider::COMMON );

			return $common->rgar( $map, 'hash_map', array() );
		} );

		$container->add( self::HASH_MAP_CSS, function () use ( $container ) {
			if ( ! file_exists( $this->plugin_path . 'assets/css/dist/assets.php' ) ) {
				return array();
			}

			$map    = require( $this->plugin_path . 'assets/css/dist/assets.php' );
			$common = $container->get( Utils_Service_Provider::COMMON );

			return $common->rgar( $map, 'hash_map', array() );
		} );

		$container->add( self::ASSET_PROCESSOR, function () use ( $container ) {
			$js_map        = $container->get( self::HASH_MAP_JS );
			$js_asset_path = sprintf( '%sassets/js/dist/', $this->plugin_path );
			$js_pattern    = 'gravitysmtp/assets/js/dist';

			$css_map = $container->get( self::HASH_MAP_CSS );
			$css_asset_path = sprintf( '%sassets/css/dist/', $this->plugin_path );
			$css_pattern    = 'gravitysmtp/assets/css/dist';

			$processor  = new Asset_Processor( $js_map, $css_map, $js_asset_path, $css_asset_path, $js_pattern, $css_pattern, 'GRAVITYSMTP_DEV_TIME_AS_VER' );

			return $processor;
		} );
	}

	public function is_smtp_page() {
		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		return strncmp( $page, 'gravitysmtp', 11 ) === 0;
	}

	public function init( Service_Container $container ) {
		$version = $container->get( self::ENVIRONMENT_DETAILS )->get_version();
		$min     = $container->get( self::ENVIRONMENT_DETAILS )->get_min();

		wp_register_script( 'gravitysmtp_vendor_admin', $this->plugin_url . "/assets/js/dist/vendor-admin{$min}.js", array(), $version, true );
		wp_register_script( 'gravitysmtp_scripts_admin', $this->dev_plugin_url . "/scripts-admin{$min}.js", array( 'gravitysmtp_vendor_admin' ), $version, true );
		wp_register_style( 'gravitysmtp_styles_admin_components', $this->plugin_url . "/assets/css/dist/admin-components{$min}.css", array(), $version );
		wp_register_style( 'gravitysmtp_styles_admin', $this->plugin_url . "/assets/css/dist/admin{$min}.css", array(), $version );
		wp_register_style( 'gravitysmtp_styles_admin_icons', $this->plugin_url . "/assets/css/dist/admin-icons{$min}.css", array( 'gravitysmtp_styles_admin' ), $version );
		wp_register_style( 'gravitysmtp_styles_base', $this->plugin_url . "/assets/css/dist/base{$min}.css", array( 'gravitysmtp_styles_admin_components' ), $version );

		if ( $this->is_smtp_page() ) {
			add_action( 'admin_enqueue_scripts', function () {
				wp_enqueue_script( 'gravitysmtp_scripts_admin' );
				wp_enqueue_style( 'gravitysmtp_styles_admin_icons' );
			} );

			add_action( 'admin_enqueue_scripts', function () use ( $container ) {
				$container->get( self::ASSET_PROCESSOR )->process_assets();
			}, 9999 );
		}
	}

}
