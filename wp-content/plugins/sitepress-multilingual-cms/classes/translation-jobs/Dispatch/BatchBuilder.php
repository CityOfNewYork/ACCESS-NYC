<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\FP\Curryable;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;

use function WPML\FP\curryN;
use function WPML\FP\pipe;

/**
 * Class BatchBuilder
 *
 * @phpstan-type curried "__CURRIED_PLACEHOLDER__"
 */
class BatchBuilder {
	use Curryable;

	public static function init() {}

	/**
	 * @param array  $data
	 * @param string $sourceLanguage
	 * @param array  $translators
	 *
	 * @return callable|\WPML_TM_Translation_Batch|null
	 *
	 * @phpstan-template A1 of array|curried
	 * @phpstan-template A2 of string|curried
	 * @phpstan-template A3 of array|curried
	 * @phpstan-template P1 of array
	 * @phpstan-template P2 of string
	 * @phpstan-template P3 of array
	 * @phpstan-template R of \WPML_TM_Translation_Batch|null
	 *
	 * @phpstan-param ?A1 $data
	 * @phpstan-param ?A2 $sourceLanguage
	 * @phpstan-param ?A3 $translators
	 *
	 * @phpstan-return ($data is P1
	 *  ? ($sourceLanguage is P2
	 *    ? ($translators is P3
	 *      ? R
	 *      : callable(P3=):R)
	 *    : ($translators is P3
	 *      ? callable(P2=):R
	 *      : callable(P2=,P3=):R)
	 *  )
	 *  : ($sourceLanguage is P2
	 *    ? ($translators is P3
	 *      ? callable(P1=):R
	 *      : callable(P1=,P3=):R)
	 *    : ($translators is P3
	 *      ? callable(P1=,P2=):R
	 *      : callable(P1=,P2=,P3=):R)
	 *  )
	 * )
	 */
	public static function buildPostsBatch( $data = null, $sourceLanguage = null, $translators = null ) {
		return call_user_func_array(
			curryN(
				3,
				function ( $data, $sourceLanguage, $translators ) {
					return self::build(
						'Translation-%s-%s',
						self::getPostElements(),
						$data,
						$sourceLanguage,
						$translators
					);
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param array  $data
	 * @param string $sourceLanguage
	 * @param array  $translators
	 *
	 * @return callable|\WPML_TM_Translation_Batch|null
	 *
	 * @phpstan-template A1 of array|curried
	 * @phpstan-template A2 of string|curried
	 * @phpstan-template A3 of array|curried
	 * @phpstan-template P1 of array
	 * @phpstan-template P2 of string
	 * @phpstan-template P3 of array
	 * @phpstan-template R of \WPML_TM_Translation_Batch|null
	 *
	 * @phpstan-param ?A1 $data
	 * @phpstan-param ?A2 $sourceLanguage
	 * @phpstan-param ?A3 $translators
	 *
	 * @phpstan-return ($data is P1
	 *  ? ($sourceLanguage is P2
	 *    ? ($translators is P3
	 *      ? R
	 *      : callable(P3=):R)
	 *    : ($translators is P3
	 *      ? callable(P2=):R
	 *      : callable(P2=,P3=):R)
	 *  )
	 *  : ($sourceLanguage is P2
	 *    ? ($translators is P3
	 *      ? callable(P1=):R
	 *      : callable(P1=,P3=):R)
	 *    : ($translators is P3
	 *      ? callable(P1=,P2=):R
	 *      : callable(P1=,P2=,P3=):R)
	 *  )
	 * )
	 */
	public static function buildStringsBatch( $data = null, $sourceLanguage = null, $translators = null ) {
		return call_user_func_array(
			curryN(
				3,
				function ( $data, $sourceLanguage, $translators ) {
					return self::build(
						'Strings translation-%s-%s',
						self::getStringElements(),
						$data,
						$sourceLanguage,
						$translators
					);
				}
			),
			func_get_args()
		);
	}

	/**
	 * @param array $postsForTranslation
	 * @param string $sourceLanguage
	 *
	 * @return callable|array
	 *
	 * @phpstan-template A1 of array|curried
	 * @phpstan-template A2 of string|curried
	 * @phpstan-template P1 of array
	 * @phpstan-template P2 of string
	 * @phpstan-template R of array
	 *
	 * @phpstan-param ?A1 $postsForTranslation
	 * @phpstan-param ?A2 $sourceLanguage
	 *
	 * @phpstan-return ($postsForTranslation is P1
	 *  ? ($sourceLanguage is P2 ? R : callable(P2=):R)
	 *  : ($sourceLanguage is P2 ? callable(P1=):R : callable(P1=,P2=):R)
	 * )
	 */
	public static function getPostElements( $postsForTranslation = null, $sourceLanguage = null ) {
		return call_user_func_array(
			curryN(
				2,
				function ( $postsForTranslation, $sourceLanguage ) {
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
				}
			),
			func_get_args()
		);
	}


	/**
	 * @param array $stringsForTranslation
	 * @param string $sourceLanguage
	 *
	 * @return callable|array
	 *
	 * @phpstan-template A1 of array|curried
	 * @phpstan-template A2 of string|curried
	 * @phpstan-template P1 of array
	 * @phpstan-template P2 of string
	 * @phpstan-template R of array
	 *
	 * @phpstan-param ?A1 $stringsForTranslation
	 * @phpstan-param ?A2 $sourceLanguage
	 *
	 * @phpstan-return ($stringsForTranslation is P1
	 *  ? ($sourceLanguage is P2 ? R : callable(P2=):R)
	 *  : ($sourceLanguage is P2 ? callable(P1=):R : callable(P1=,P2=):R)
	 * )
	 */
	public static function getStringElements( $stringsForTranslation = null, $sourceLanguage = null ) {
		return call_user_func_array(
			curryN(
				2,
				function ( $stringsForTranslation, $sourceLanguage ) {
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
				}
			),
			func_get_args()
		);
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
