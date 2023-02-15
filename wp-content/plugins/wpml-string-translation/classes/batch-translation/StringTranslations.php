<?php

namespace WPML\ST\Batch\Translation;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Wrapper;
use WPML\Setup\Option;
use WPML\ST\API\Fns as ST_API;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

/**
 * Class StringTranslations
 *
 * @package WPML\ST\Batch\Translation
 * @method static callable|void save( ...$element_type_prefix, ...$job, ...$decoder ) :: string → object → ( string → string → string ) → void
 * @method static callable|void addExisting( ...$prevTranslations, ...$package, ...$lang ) :: [WPML_TM_Translated_Field] → object → string → [WPML_TM_Translated_Field]
 * @method static callable|bool isTranslated( ...$field ) :: object → bool
 * @method static callable|bool isBatchId( ...$str ) :: string → bool
 * @method static callable|bool isBatchField( ...$field ) :: object → bool
 * @method static callable|string decodeStringId( ...$str ) :: string → string
 * @method static callable|void markTranslationsAsInProgress( ...$getJobStatus, ...$hasTranslation, ...$addTranslation, ...$post, ...$element) :: callable -> callable -> callable -> WPML_TM_Translation_Batch_Element -> \stdClass -> void
 * @method static callable|void cancelTranslations(...$job) :: \WPML_TM_Job_Entity -> void
 * @method static callable|void updateStatus(...$element_type_prefix, ...$job) :: string -> \stdClass -> void
 */
class StringTranslations {

	use Macroable;

	public static function init() {

		self::macro( 'isBatchId', Str::startsWith( Module::STRING_ID_PREFIX ) );

		self::macro( 'isTranslated', Obj::prop( 'field_translate' ) );

		self::macro(
			'isBatchField',
			curryN(
				1,
				function( $field ) {
					return self::isBatchId( Obj::prop( 'field_type', $field ) );
				}
			)
		);

		self::macro( 'decodeStringId', Str::replace( Module::STRING_ID_PREFIX, '' ) );

		self::macro(
			'save',
			curryN(
				3,
				function ( $element_type_prefix, $job, callable $decoder ) {
					if ( $element_type_prefix === 'st-batch' ) {

						// $decodeField :: field → string
						$decodeField = pipe(
							Obj::props( [ 'field_data_translated', 'field_format' ] ),
							spreadArgs( $decoder )
						);

						// $getStringId :: field → int
						$getStringId = pipe( Obj::prop( 'field_type' ), self::decodeStringId() );

						// $saveTranslation :: field → void
						$saveTranslation = Fns::converge(
							ST_API::saveTranslation( Fns::__, $job->language_code, Fns::__, ICL_TM_COMPLETE ),
							[ $getStringId, $decodeField ]
						);

						Wrapper::of( $job->elements )
							   ->map( Fns::filter( Logic::allPass( [ self::isTranslated(), self::isBatchField() ] ) ) )
							   ->map( Fns::each( $saveTranslation ) );
					}
				}
			)
		);

		self::macro(
			'updateStatus',
			curryN(
				2,
				function ( $element_type_prefix, $job ) {
					if ( $element_type_prefix === 'st-batch' ) {
						// $getStringId :: field → int
						$getStringId = pipe( Obj::prop( 'field_type' ), self::decodeStringId() );

						$updateStatus = ST_API::updateStatus( Fns::__, $job->language_code, ICL_TM_IN_PROGRESS );

						\wpml_collect( $job->elements )
						->filter( self::isBatchField() )
						->map( $getStringId )
						->each( $updateStatus );
					}
				}
			)
		);

		self::macro(
			'cancelTranslations',
			curryN(
				1,
				function ( $job ) {
					if ( $job instanceof \WPML_TM_Post_Job_Entity && $job->get_type() === 'st-batch_strings' ) {
						$language = $job->get_target_language();

						// $getTranslations :: $stringId -> [stringId, translation]
						$getTranslations = function ( $stringId ) use ( $language ) {
							return [
								'string_id'   => $stringId,
								'translation' => Obj::pathOr( '', [ $language, 'value' ], ST_API::getTranslations( $stringId ) ),
							];
						};

						// $cancelStatus :: [stringId, translation] -> int
						$cancelStatus = Logic::ifElse( Obj::prop( 'translation' ), Fns::always( ICL_TM_COMPLETE ), Fns::always( ICL_TM_NOT_TRANSLATED ) );

						// $cancel :: [stringId, translation] -> void
						$cancel = Fns::converge(
							ST_API::updateStatus( Fns::__, $language, Fns::__ ),
							[ Obj::prop( 'string_id' ), $cancelStatus ]
						);

						\wpml_collect( $job->get_elements() )
							->map( invoke( 'get_type' ) )
							->filter( Fns::unary( self::isBatchId() ) )
							->map( self::decodeStringId() )
							->map( $getTranslations )
							->map( Fns::tap( $cancel ) );
					}
				}
			)
		);

		self::macro(
			'addExisting',
			curryN(
				3,
				function ( $prevTranslations, $package, $lang ) {

					// $getTranslation :: lang → { translate, ... } → int → { id, translation } | null
					$getTranslation = curryN(
						3,
						function ( $lang, $data, $stringId ) {
							if ( $data['translate'] === 1 && self::isBatchId( $stringId ) ) {
								return (object) [
									'id'          => $stringId,
									'translation' => base64_encode( ST_API::getTranslation( self::decodeStringId( $stringId ), $lang ) ),
								];
							}

							return null;
						}
					);

					// $createField :: string → WPML_TM_Translated_Field
					$createField = function ( $translation ) {
						return make( 'WPML_TM_Translated_Field', [ '', '', $translation, false ] );
					};

					// $updatePrevious :: [a] → { id, translate } → [a]
					$updatePrevious = function ( $prev, $string ) {
						$prev[ $string->id ] = $string->translation;

						return $prev;
					};

					// $hasTranslation :: { id, translation } | null → bool
					$hasTranslation = Obj::prop( 'translation' );

					return Wrapper::of( $package['contents'] )
							  ->map( Fns::map( $getTranslation( $lang ) ) )
							  ->map( Fns::filter( $hasTranslation ) )
							  ->map( Fns::map( Obj::evolve( [ 'translation' => $createField ] ) ) )
							  ->map( Fns::reduce( $updatePrevious, $prevTranslations ) )
							  ->get();
				}
			)
		);

		self::macro(
			'markTranslationsAsInProgress',
			curryN(
				3,
				function ( $getJobStatus, $element, $post ) {
					if ( $element instanceof \WPML_TM_Translation_Batch_Element && $element->get_element_type() === 'st-batch' ) {
						$statuses = \wpml_collect( $getJobStatus( $post->post_id ) );

						$addTranslationWithStatus = function ( $stringId, $targetLanguage ) use ( $statuses ) {
							$status = Option::shouldTranslateEverything()
								? ICL_TM_IN_PROGRESS
								: $statuses->get( $targetLanguage, ICL_STRING_TRANSLATION_NOT_TRANSLATED );
							ST_API::updateStatus( $stringId, $targetLanguage, $status );
						};

						\wpml_collect( $post->string_data )
						->keys()
						->map( Fns::unary( StringTranslations::decodeStringId() ) )
						->map( Fns::unary( 'intval' ) )
						->crossJoin( array_keys( $element->get_target_langs() ) )
						->map( Fns::tap( spreadArgs( $addTranslationWithStatus ) ) );
					}
				}
			)
		);
	}

}

StringTranslations::init();
