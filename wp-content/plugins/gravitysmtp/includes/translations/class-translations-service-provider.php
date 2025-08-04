<?php

namespace Gravity_Forms\Gravity_SMTP\Translations;

use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Translations_Service_Provider extends Config_Service_Provider {

	const TRANSLATIONSPRESS = 'translationspress';

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( self::TRANSLATIONSPRESS, function () use ( $container ) {
			return new TranslationsPress( 'gravitysmtp', $container->get( Utils_Service_Provider::COMMON ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'gravitysmtp_post_activation', function () use ( $container ) {
			$container->get( self::TRANSLATIONSPRESS )->install();
		} );
		add_action( 'delete_site_transient_update_plugins', function () use ( $container ) {
			$container->get( self::TRANSLATIONSPRESS )->refresh_all_translations();
		} );
		add_action( 'update_option_WPLANG', function ( $old_language, $new_language ) use ( $container ) {
			$container->get( self::TRANSLATIONSPRESS )->install_on_wplang_update( $old_language, $new_language );
		}, 10, 2 );
		add_action( 'upgrader_process_complete', function ( $upgrader_object, $hook_extra ) use ( $container ) {
			$container->get( self::TRANSLATIONSPRESS )->upgrader_process_complete( $upgrader_object, $hook_extra );
		}, 10, 2 );
		add_filter( 'translations_api', function ( $result, $requested_type, $args ) use ( $container ) {
			return $container->get( self::TRANSLATIONSPRESS )->translations_api( $result, $requested_type, $args );
		}, 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', function ( $value ) use ( $container ) {
			return $container->get( self::TRANSLATIONSPRESS )->site_transient_update_plugins( $value );
		} );
	}
}