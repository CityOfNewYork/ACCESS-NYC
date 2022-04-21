<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\FP\Curryable;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use function WPML\FP\pipe;

/**
 * Class BatchBuilder
 *
 * @method static callable|\WPML_TM_Translation_Batch|null buildPostsBatch( ...$data, ...$sourceLanguage, ...$translators ) - Curried :: array->string->array->\WPML_TM_Translation_Batch|null
 * @method static callable|\WPML_TM_Translation_Batch|null buildStringsBatch( ...$data, ...$sourceLanguage, ...$translators ) - Curried :: array->string->array->\WPML_TM_Translation_Batch|null
 * @method static callable|array getPostElements( ...$postsForTranslation, ...$sourceLanguage ) - Curried :: array->string->array
 * @method static callable|array getStringElements( ...$stringsForTranslation, ...$sourceLanguage ) - Curried :: array->string->array
 */
class BatchBuilder {
	use Curryable;

	public static function init() {

		self::curryN( 'buildPostsBatch', 3, function ( array $data, $sourceLanguage, array $translators ) {
			return self::build(
				'Translation-%s-%s',
				self::getPostElements(),
				$data,
				$sourceLanguage,
				$translators
			);
		} );

		self::curryN( 'buildStringsBatch', 3, function ( array $data, $sourceLanguage, array $translators ) {
			return self::build(
				'Strings translation-%s-%s',
				self::getStringElements(),
				$data,
				$sourceLanguage,
				$translators
			);
		} );

		self::curryN( 'getPostElements', 2, function ( $postsForTranslation, $sourceLanguage ) {
			$elements = [];

			foreach ( $postsForTranslation as $postId => $postData ) {
				$elements[] = new \WPML_TM_Translation_Batch_Element(
					$postId,
					$postData['type'],
					$sourceLanguage,
					array_fill_keys( $postData['target_languages'], \TranslationManagement::TRANSLATE_ELEMENT_ACTION ),
					Obj::propOr( [], 'media', $postData )
				);
			}

			return $elements;
		} );

		self::curryN( 'getStringElements', 2, function ( $stringsForTranslation, $sourceLanguage ) {
			$elements = [];

			$setTranslateAction = pipe(
				Fns::map( pipe( Lst::makePair( \TranslationManagement::TRANSLATE_ELEMENT_ACTION ), Lst::reverse() ) ),
				Lst::fromPairs()
			);

			foreach ( $stringsForTranslation as $stringId => $targetLanguages ) {
				$elements[] = new \WPML_TM_Translation_Batch_Element(
					$stringId,
					'string',
					$sourceLanguage,
					$setTranslateAction( $targetLanguages )
				);
			}

			return $elements;
		} );
	}

	/**
	 * @param string $batchNameTemplate
	 * @param callable $buildElementStrategy
	 * @param array $data
	 * @param string $sourceLanguage
	 * @param array $translators
	 *
	 * @return \WPML_TM_Translation_Batch|null
	 */
	private static function build( $batchNameTemplate, callable $buildElementStrategy, array $data, $sourceLanguage, array $translators ) {
		$targetLanguagesString = pipe( Lst::flatten(), 'array_unique', Lst::join( '|' ) );
		$idsHash               = pipe( 'array_keys', Lst::join( '-' ), 'md5', Str::sub( 16 ) );

		$batchName = sprintf(
			$batchNameTemplate,
			$targetLanguagesString( $data ),
			$idsHash( $data )
		);

		$elements = apply_filters(
			'wpml_tm_batch_factory_elements',
			$buildElementStrategy( $data, $sourceLanguage ),
			$batchName
		);

		return $elements ? new \WPML_TM_Translation_Batch( $elements, $batchName, $translators, null ) : null;
	}
}

BatchBuilder::init();