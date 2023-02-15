<?php


namespace WPML\Media\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;

use WPML\Utilities\KeyedLock;

use WPML\FP\Left;
use WPML\FP\Right;

use function WPML\Container\make;

class PrepareSetup implements IHandler {
	const LOCK_RELEASE_TIMEOUT = 2 * MINUTE_IN_SECONDS;

	public function run( Collection $data ) {
		if (  !defined( 'WPML_MEDIA_VERSION' ) || !class_exists( 'WPML_Media_Set_Posts_Media_Flag_Factory' ) ) {
			return Left::of( [ 'key' => false ] );
		}

		$lock = make( KeyedLock::class, [ ':name' => self::class ] );
		$key  = $lock->create( $data->get( 'key' ), self::LOCK_RELEASE_TIMEOUT );

		if ( $key ) {
			$mediaFlag = make( \WPML_Media_Set_Posts_Media_Flag_Factory::class)->create();
			$mediaFlag->clear_flags();

			return Right::of( [ 'key' => $key, ] );
		} else {
			return Left::of( [ 'key' => 'in-use', ] );
		}
	}
}
