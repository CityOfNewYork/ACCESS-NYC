<?php

namespace WPML\Settings\PostType;

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Lst;
use WPML\FP\Wrapper;
use WPML\Setup\Option;
use WPML\WP\OptionManager;

class Automatic {

	const GROUP       = 'post-type';
	const FROM_CONFIG = 'automatic-config';
	const OVERRIDE    = 'automatic-override';

	public static function saveFromConfig( array $config ) {
		$getCustomTypes = Obj::pathOr( [], [ 'wpml-config', 'custom-types', 'custom-type' ] );
		$keyByPostType  = Lst::keyBy( 'value' );
		$getAutomaticSetting = Fns::map( Obj::pathOr( true, [ 'attr', 'automatic' ] ) );

		Wrapper::of( $config )
		       ->map( $getCustomTypes )
		       ->map( $keyByPostType )
		       ->map( $getAutomaticSetting )
		       ->map( Fns::map( Cast::toBool() ) )
		       ->map( OptionManager::update( self::GROUP, self::FROM_CONFIG ) );
	}

	public static function isAutomatic( $postType ) {
		$fromConfig = Obj::propOr( true, $postType, OptionManager::getOr( [], self::GROUP, self::FROM_CONFIG ) );
		$current    = OptionManager::getOr( [], self::GROUP, self::OVERRIDE );

		return Obj::propOr( $fromConfig, $postType, $current );
	}

	public static function set( $postType, $state ) {
		$current              = OptionManager::getOr( [], self::GROUP, self::OVERRIDE );
		$current[ $postType ] = $state;
		OptionManager::update( self::GROUP, self::OVERRIDE, $current );
	}

	public static function shouldTranslate( $postType ) {
		return Option::shouldTranslateEverything() && self::isAutomatic( $postType );
	}
}
