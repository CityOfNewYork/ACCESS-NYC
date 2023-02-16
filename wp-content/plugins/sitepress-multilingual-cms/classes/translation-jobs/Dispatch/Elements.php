<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
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
		$translationActions = filter_var_array(
			Obj::propOr( [], 'tr_action', $data ),
			FILTER_SANITIZE_NUMBER_INT
		);
		$sourceLanguage     = filter_var( $data['translate_from'], FILTER_SANITIZE_STRING );

		$targetLanguages = self::getTargetLanguages( $translationActions );
		$translators     = self::getTranslators( $sourceLanguage, $targetLanguages );

		$elementsForTranslation = self::getElements( $messages, $data[ $type ], $targetLanguages );

		$batch = $buildBatch( $elementsForTranslation, $sourceLanguage, $translators );
		$batch && $sendBatch( $messages, $batch );
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
		$targetLanguages
	) {
		$getElementsToTranslate = pipe( Fns::filter( Obj::prop( 'checked' ) ), Lst::keyBy( 'checked' ) );
		$elementsIds            = $getElementsToTranslate( $data );

		list( $elementsToTranslation, $ignoredElementsMessages ) = static::filterElements(
			$messages,
			$elementsIds,
			$targetLanguages
		);

		$messages->showForPosts( $ignoredElementsMessages, 'information' );

		return array_filter( $elementsToTranslation, pipe( Obj::prop( 'target_languages' ), Lst::length() ) );
	}

	/**
	 * @param int    $elementId
	 * @param string $elementType
	 * @param string $language
	 *
	 * @return bool
	 */
	protected static function hasInProgressJob( $elementId, $elementType, $language ) {
		$job = Jobs::getElementJob( $elementId, $elementType, $language );

		return $job && ICL_TM_IN_PROGRESS === (int) $job->status && ! $job->needs_update;
	}

	/**
	 * @param Messages $messages
	 * @param array    $elementsData
	 * @param array    $targetLanguages
	 *
	 * @return array
	 */
	abstract protected static function filterElements( Messages $messages, $elementsData, $targetLanguages );
}
