<?php

namespace Gravity_Forms\Gravity_SMTP\Experimental_Features;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Repository;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Experimental_Features_Service_Provider extends Service_Provider {

	const EXPERIMENTAL_FEATURE_HANDLER = 'experimental_feature_handler';

	public function register( Service_Container $container ) {
		$container->add( self::EXPERIMENTAL_FEATURE_HANDLER, function() use ( $container ) {
			return new Experiment_Features_Handler( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_filter( Feature_Flag_Repository::FILTER_SINGLE_FEATURE_FLAG, function( $is_enabled, $flag_slug ) use ( $container ) {
			return $container->get( self::EXPERIMENTAL_FEATURE_HANDLER )->feature_flag_callback( $is_enabled, $flag_slug );
		}, 10, 2 );
	}

}