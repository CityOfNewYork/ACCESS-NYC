<?php

namespace WPML\TM\API;

use WPML\Collect\Support\Traits\Macroable;
use WPML\Element\API\PostTranslations;
use WPML\Element\API\TranslationsRepository;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\Settings\PostType\Automatic;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\API\Job\Map;
use WPML\TM\Records\UpdateTranslationReviewStatus;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\pipe;

/**
 * Class Jobs
 * @package WPML\TM\API
 *
 * @method static callable|null|\stdClass getPostJob( ...$postId, ...$postType, ...$language ) : Curried:: int->string->string->null|\stdClass
 * @method static callable|null|\stdClass getElementJob( ...$postId, ...$elementType, ...$language ) : Curried:: int->string->string->null|\stdClass
 * @method static callable|null|\stdClass getTridJob( ...$trid, ...$language ) : Curried:: int->string->null|\stdClass
 * @method static callable|false|\stdClass get( ...$jobId ) : Curried:: int->false|\stdClass
 * @method static callable|void setStatus( ...$jobId, $jobStatus ) : Curried:: int->int->int
 * @method static callable|void setNotTranslatedStatus( ...$jobId )  : Curried:: int->int
 * @method static callable|void setReviewStatus( ...$jobId, $reviewStatus ) : Curried:: int->int->int
 * @method static callable|void clearReviewStatus( ...$jobId ) : Curried:: int->int->int
 * @method static callable|array getTranslation( ...$job ) - Curried :: \stdClass->array
 * @method static callable|int getTranslatedPostId( ...$job ) - Curried :: \stdClass->int
 * @method static callable|string getEditUrl( ...$returnUrl, ...$job ) - Curried :: string->int->string
 * @method static callable|void incrementRetryCount( ...$jobId ) : Curried:: int->void
 * @method static callable|void setTranslated( ...$jobId, ...$status ) - Curried :: int->bool->int
 * @method static callable|void clearTranslated( ...$jobId ) - Curried :: int->int
 * @method static callable|int clearAutomatic( ...$jobId ) - Curried :: int->int
 * @method static callable|void delete( ...$jobId ) - Curried :: int->void
 * @method static callable|bool isEligibleForAutomaticTranslations( ...$jobId ) - Curried :: int->bool
 */
class Jobs {
	use Macroable;

	const SENT_MANUALLY      = 1;
	const SENT_VIA_BASKET    = 2;
	const SENT_AUTOMATICALLY = 3;
	const SENT_FROM_REVIEW   = 4;
	const SENT_RETRY         = 5;

	public static function init() {

		self::macro( 'getPostJob', curryN( 3, function ( $postId, $postType, $language ) {
			return self::getElementJob( $postId, 'post_' . $postType, $language );
		} ) );

		self::macro( 'getElementJob', curryN( 3, function ( $postId, $elementType, $language ) {
			global $sitepress;

			$trid = $sitepress->get_element_trid( $postId, $elementType );

			return self::getTridJob( $trid, $language );
		} ) );

		self::macro( 'getTridJob', curryN( 2, function ( $trid, $language ) {
			$result = TranslationsRepository::getByTridAndLanguage( $trid, $language );
			if ( $result ) {
				return $result;
			}
			$jobId = wpml_load_core_tm()->get_translation_job_id( $trid, $language );

			return $jobId ? wpml_tm_load_job_factory()->get_translation_job_as_stdclass( $jobId ) : null;
		} ) );

		self::macro( 'get', curryN( 1, function ( $jobId ) {
			return wpml_tm_load_job_factory()->get_translation_job_as_stdclass( $jobId );
		} ) );

		self::macro( 'setStatus', curryN( 2, function ( $jobId, $status ) {
			return self::updateTranslationStatusField( $jobId, 'status', $status );
		} ) );

		self::macro( 'setNotTranslatedStatus', self::setStatus( Fns::__, ICL_TM_NOT_TRANSLATED ) );

		self::macro( 'setReviewStatus', curryN( 2, function ( $jobId, $status ) {
			return self::updateTranslationStatusField( $jobId, 'review_status', $status, '%s' );
		} ) );

		self::macro( 'clearReviewStatus', self::setReviewStatus( Fns::__, null ) );

		self::macro( 'incrementRetryCount', curryN( 1, function ( $jobId ) {
			$job = self::get( $jobId );

			return $job ? self::updateTranslationStatusField(
				$jobId,
				'ate_comm_retry_count',
				$job->ate_comm_retry_count + 1 ) : null;
		} ) );

		self::macro( 'getTranslation', curryN( 1, Fns::converge( Obj::prop(), [
			Obj::prop( 'language_code' ),
			pipe( Obj::prop( 'original_doc_id' ), Fns::memorize( PostTranslations::get() ) )
		] ) ) );

		self::macro( 'getTranslatedPostId', curryN( 1, pipe( self::getTranslation(), Obj::prop( 'element_id' ) ) ) );

		self::macro( 'getEditUrl', curryN( 2, function ( $returnUrl, $jobId ) {
			$jobEditUrl = admin_url( 'admin.php?page='
			                         . WPML_TM_FOLDER
			                         . '/menu/translations-queue.php&job_id='
			                         . $jobId
			                         . '&return_url=' . urlencode( $returnUrl ) );

			return apply_filters( 'icl_job_edit_url', $jobEditUrl, $jobId );
		} ) );

		self::macro( 'setTranslated', curryN( 2, function ( $jobId, $status ) {
			return self::updateTranslateJobField( $jobId, 'translated', $status );
		} ) );

		self::macro( 'clearTranslated', self::setTranslated( Fns::__, false ) );

		self::macro( 'clearAutomatic', curryN( 1, function ( $jobId ) {
			return self::updateTranslateJobField( $jobId, 'automatic', 0 );
		} ) );

		self::macro( 'delete', curryN( 1, function ( $jobId ) {
			/** @var \wpdb $wpdb */
			global $wpdb;

			$rid           = Map::fromJobId( $jobId );
			$previousState = \WPML_TM_ICL_Translation_Status::makeByRid( $rid )
			                                                ->previous()
			                                                ->getOrElse( null );
			if ( $previousState ) {
				$wpdb->update(
					$wpdb->prefix . 'icl_translation_status',
					Obj::pick( [ 'status', 'translator_id', 'needs_update', 'md5 ' ], $previousState ),
					[ 'rid' => $rid ]
				);
			} else {
				$wpdb->delete(
					$wpdb->prefix . 'icl_translation_status',
					[ 'rid' => $rid ],
					[ 'rid' => '%d' ]
				);
			}
			$wpdb->delete(
				$wpdb->prefix . 'icl_translate_job',
				[ 'job_id' => $jobId ],
				[ 'job_id' => '%d' ]
			);
		} ) );

		self::macro( 'isEligibleForAutomaticTranslations', curryN( 1, Fns::memorize( function ( $wpmlJobId ) {
			$getPostType = pipe( Obj::prop( 'original_post_type' ), Str::replace( 'post_', '' ) );

			return Maybe::of( $wpmlJobId )
			            ->map( Jobs::get() )
			            ->map( Logic::both(
				            pipe( $getPostType, [ Automatic::class, 'shouldTranslate' ] ),
				            pipe( Obj::prop( 'language_code' ), LanguageMappings::isCodeEligibleForAutomaticTranslations() )
			            ) )
			            ->getOrElse( false );
		} ) ) );
	}

	/**
	 * @return string
	 */
	public static function getCurrentUrl() {
		$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || Obj::prop( 'SERVER_PORT', $_SERVER ) == 443 ) ? "https://" : "http://";

		return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	private static function updateTranslationStatusField( $jobId, $fieldName, $newValue, $fieldType = '%d' ) {
		global $wpdb;

		$query = "
				UPDATE {$wpdb->prefix}icl_translation_status
				SET `{$fieldName}` = {$fieldType}
				WHERE rid = (
				    SELECT rid FROM {$wpdb->prefix}icl_translate_job
				    WHERE job_id = %d
				)
			";
		$wpdb->query( $wpdb->prepare( $query, $newValue, $jobId ) );

		return $jobId;
	}

	private static function updateTranslateJobField( $jobId, $fieldName, $newValue ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'icl_translate_job',
			[ $fieldName => $newValue ],
			[ 'job_id' => $jobId ]
		);

		return $jobId;
	}
}

Jobs::init();
