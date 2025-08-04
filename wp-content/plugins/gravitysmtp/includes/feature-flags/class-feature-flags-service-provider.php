<?php

namespace Gravity_Forms\Gravity_SMTP\Feature_Flags;

use Gravity_Forms\Gravity_SMTP\Feature_Flags\Config\Feature_Flags_Config;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Feature_Flags_Service_Provider extends Config_Service_Provider {

	const FEATURE_FLAG_REPOSITORY = 'feature_flag_repository';
	const FEATURE_FLAG_MANAGER = 'feature_flag_manager';

	const FEATURE_FLAGS_CONFIG = 'feature_flags_config';

	protected $configs = array(
		self::FEATURE_FLAGS_CONFIG => Feature_Flags_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::FEATURE_FLAG_REPOSITORY, function () {
			return new Feature_Flag_Repository();
		} );

		$container->add( self::FEATURE_FLAG_MANAGER, function () use ( $container ) {
			return new Feature_Flag_Manager( $container->get( self::FEATURE_FLAG_REPOSITORY ) );
		} );
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {

	}

}