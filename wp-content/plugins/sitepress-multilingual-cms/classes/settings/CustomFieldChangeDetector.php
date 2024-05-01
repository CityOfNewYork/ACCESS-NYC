<?php

namespace WPML\TM\Settings;

use WPML\Core\BackgroundTask\Service\BackgroundTaskService;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class CustomFieldChangeDetector implements \IWPML_Backend_Action, \IWPML_DIC_Action {
	const PREVIOUS_SETTING = 'previous-custom-fields-to-translate';
	const DETECTED_SETTING = 'detected-custom-fields-to-translate';

	/** @var BackgroundTaskService */
	private $backgroundTaskService;

	/**
	 * @param BackgroundTaskService $backgroundTaskService
	 */
	public function __construct( BackgroundTaskService $backgroundTaskService ) {
		$this->backgroundTaskService = $backgroundTaskService;
	}

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

	public function processNewFields() {
		$newFields = self::getDetected();
		if ( count( $newFields ) > 0 ) {
			$newFields = array_unique( $newFields );

			/** @var ProcessNewTranslatableFields $backroundTaskEndpoint */
			$backroundTaskEndpoint = make( ProcessNewTranslatableFields::class );

			$payload = wpml_collect( [ 'newFields' => $newFields ] );

			if ( $backroundTaskEndpoint->getTotalRecords( $payload ) ) {
				// We could do some optimization to avoid running again after consecutive changes on same field.
				// But currently, it's more consistent to enqueue a new task every time, since there may be cases
				// when the user is running a task affecting some custom field for long time, and wants to update again
				// and ghet the posts re-processed.
				$this->backgroundTaskService->add( $backroundTaskEndpoint, $payload );
			}

			self::remove( $newFields );
		}
	}
}
