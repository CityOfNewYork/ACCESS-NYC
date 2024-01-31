<?php

namespace WPML\TM\Jobs\Dispatch;

use Exception;
use WPML\API\Sanitize;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\User;
use WPML\TM\API\Jobs;
use function WPML\Container\make;
use function WPML\FP\pipe;

abstract class Elements {
	/**
	 * @param callable $sendBatch
	 * @param Messages $messages
	 * @param callable $buildBatch
	 * @param array    $data
	 * @param string   $type
	 */
	public static function dispatch(
		callable $sendBatch,
		Messages $messages,
		callable $buildBatch,
		$data,
		$type
	) {
		$howToHandleExisting = Obj::propOr(\WPML_TM_Translation_Batch::HANDLE_EXISTING_LEAVE, 'wpml-how-to-handle-existing', $data );
		$translateAutomatically = Relation::propEq( 'wpml-how-to-translate', 'automatic', $data );

		$translationActions = filter_var_array(
			Obj::propOr( [], 'tr_action', $data ),
			FILTER_SANITIZE_NUMBER_INT
		);
		$sourceLanguage = Sanitize::stringProp( 'translate_from', $data );

		$targetLanguages = self::getTargetLanguages( $translationActions );
		$translators     = self::getTranslators( $sourceLanguage, $targetLanguages );

		$elementsForTranslation = self::getElements( $messages, $data[ $type ], $targetLanguages, $howToHandleExisting, $translateAutomatically );

		$batch = $buildBatch( $elementsForTranslation, $sourceLanguage, $translators );
		if ( $batch ) {
			$batch->setTranslationMode( Relation::propEq( 'wpml-how-to-translate', 'automatic', $data ) ? 'auto' : 'manual' );
			$batch->setHowToHandleExisting( $howToHandleExisting );
			$sendBatch( $messages, $batch );
		}
	}

	private static function getTargetLanguages( $translationActions ) {
		return array_keys(
			array_filter( $translationActions, function ( $action ) {
				return (int) $action === \TranslationManagement::TRANSLATE_ELEMENT_ACTION;
			} )
		);
	}

	private static function getTranslators( $sourceLanguage, $targetLanguages ) {
		$records = make( \WPML_Translator_Records::class );
		$getTranslator = function ( $lang ) use ( $sourceLanguage, $records ) {
			$translators = $records->get_users_with_languages( $sourceLanguage, [ $lang ] );
			return count( $translators ) ? $translators[0] : User::getCurrent();
		};

		$translators = wpml_collect( $targetLanguages )
			->map( $getTranslator )
			->map( Obj::prop( 'ID') );

		return Lst::zipObj( $targetLanguages, $translators->toArray() );
	}

	private static function getElements(
		Messages $messages,
		$data,
		$targetLanguages,
		$howToHandleExisting,
		$translateAutomatically
	) {
		$getElementsToTranslate = pipe( Fns::filter( Obj::prop( 'checked' ) ), Lst::keyBy( 'checked' ) );
		$elementsIds            = $getElementsToTranslate( $data );

		list( $elementsToTranslation, $ignoredElementsMessages ) = static::filterElements(
			$messages,
			$elementsIds,
			$targetLanguages,
			$howToHandleExisting,
			$translateAutomatically
		);

		$messages->showForPosts( $ignoredElementsMessages, 'information' );

		return array_filter( $elementsToTranslation, pipe( Obj::prop( 'target_languages' ), Lst::length() ) );
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	protected static function isProgressJob( $job ) {
		return Lst::includes( (int) $job->status, [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ] ) && ! $job->needs_update;
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return bool
	 */
	protected static function isCompletedJob( $job ) {
		return (int) $job->status === ICL_TM_COMPLETE && ! $job->needs_update;
	}

	/**
	 * @param Messages $messages
	 * @param array    $elementsData
	 * @param array    $targetLanguages
	 * @param string   $howToHandleExisting
	 * @param bool     $translateAutomatically
	 *
	 * phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
	 * @return array
	 * @throws Exception Throws an exception if the method is not properly extended.
	 */
	protected static function filterElements( Messages $messages, $elementsData, $targetLanguages, $howToHandleExisting, $translateAutomatically ) {
		throw new Exception( ' this method is mandatory' );
	}

	/**
	 * @param \stdClass $job
	 * @param string|null  $howToHandleExisting
	 * @param bool $translateAutomatically
	 *
	 * @return bool
	 */
	protected static function shouldJobBeIgnoredBecauseIsCompleted( $job, $howToHandleExisting, $translateAutomatically ) {
		return $translateAutomatically && $job && self::isCompletedJob( $job ) && $howToHandleExisting === \WPML_TM_Translation_Batch::HANDLE_EXISTING_LEAVE;
	}
}
