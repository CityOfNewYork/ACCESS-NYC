<?php

namespace WPML\Media\Translate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Left;
use WPML\FP\Right;
use WPML\Media\Option;
use WPML\Utilities\KeyedLock;
use WPML\Element\API\Languages;

class PrepareForTranslation implements IHandler {
	const LOCK_RELEASE_TIMEOUT = 2 * MINUTE_IN_SECONDS;

	public function run( Collection $data ) {
		if ( $this->isThereOnlyOneActiveLanguage() || Option::isSetupFinished() ) {
			return Left::of( [ 'key' => false ] );
		}

		$lock = make( KeyedLock::class, [ ':name' => self::class ] );
		$key  = $lock->create( $data->get( 'key' ), self::LOCK_RELEASE_TIMEOUT );

		if ( $key ) {
			make( \WPML_Media_Attachments_Duplication::class )->batch_scan_prepare( false );

			return Right::of( [ 'key' => $key, ] );
		} else {
			return Left::of( [ 'key' => 'in-use', ] );
		}
	}

	/**
	 * By this situation we mean that we have only the default language and ZERO target languages!
	 * You can't have such situation after WPML Setup. You have to go to WPML > Languages and
	 * manually unselect all target languages.
	 *
	 * @return bool
	 */
	private function isThereOnlyOneActiveLanguage(): bool {
		$activeLanguages = Languages::getActive();

		return count( $activeLanguages ) === 1;
	}
}
