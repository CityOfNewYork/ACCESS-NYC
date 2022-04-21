<?php

namespace WPML\TM\Settings;

use WPML\Core\BackgroundTask;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class CustomFieldChangeDetector implements \IWPML_Backend_Action {
	const PREVIOUS_SETTING = 'previous-custom-fields-to-translate';
	const DETECTED_SETTING = 'detected-custom-fields-to-translate';
	const LOCK_TIME        = 2 * MINUTE_IN_SECONDS;

	public function add_hooks() {
		Hooks::onAction( 'wpml_after_tm_loaded', 1 )
		     ->then( [ self::class, 'getNew' ] )
		     ->then( [ self::class, 'notify' ] )
		     ->then( [ self::class, 'updatePrevious' ] );
	}

	public static function getNew() {
		if ( is_null( OptionManager::getOr( null, 'TM', self::PREVIOUS_SETTING ) ) ) {
			self::updatePrevious();
		}

		return Lst::diff(
			Repository::getCustomFieldsToTranslate() ?: [],
			OptionManager::getOr( [], 'TM', self::PREVIOUS_SETTING )
		);
	}

	public static function notify( array $newFields ) {
		if ( count( $newFields ) ) {
			OptionManager::update(
				'TM',
				self::DETECTED_SETTING,
				Lst::concat( self::getDetected(), $newFields )
			);
		}
	}

	public static function remove( array $fields ) {
		if ( count( $fields ) ) {
			OptionManager::update(
				'TM',
				self::DETECTED_SETTING,
				Lst::diff( self::getDetected(), $fields )
			);
		}
	}

	public static function updatePrevious() {
		OptionManager::update( 'TM', self::PREVIOUS_SETTING, Repository::getCustomFieldsToTranslate() );
	}

	public static function getDetected() {
		return OptionManager::getOr( [], 'TM', self::DETECTED_SETTING );
	}

	public static function processNewFields() {
		$newFields = self::getDetected();
		if ( count( $newFields ) ) {

			/**
			 * Create a lock to make sure only one background task will run at a time.
			 * We wont release the lock so it wont be able to run again until the time out is complete.
			 */

			$lock = make( 'WPML\Utilities\Lock', [ ':name' => self::class ] );
			if ( $lock->create( self::LOCK_TIME ) ) {
				BackgroundTask::add( ProcessNewTranslatableFields::class, [ 'newFields' => $newFields ] );
			}
		}
	}
}
