<?php


namespace WPML\TM\Jobs;

use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use WPML\Records\Translations as TranslationRecords;
use WPML\TM\API\Jobs;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class Manual {
	/**
	 * @param array $params
	 *
	 * @return \WPML_Translation_Job|null
	 */
	public function createOrReuse( array $params ) {
		$jobId = (int) filter_var( Obj::propOr( 0, 'job_id', $params ), FILTER_SANITIZE_NUMBER_INT );

		list( $jobId, $trid, $updateNeeded, $targetLanguageCode, $elementType ) = $this->get_job_data_for_restore( $jobId, $params );
		$sourceLangCode = filter_var( Obj::prop( 'source_language_code', $params ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( $trid && $targetLanguageCode && ( $updateNeeded || ! $jobId ) ) {
			$postId = $this->getOriginalPostId( $trid );

			if ( $postId && $this->can_user_translate( $sourceLangCode, $targetLanguageCode, $postId ) ) {
				return $this->markJobAsManual( $this->createLocalJob( $postId, $targetLanguageCode, $elementType ) );
			}
		}
		return $jobId ? $this->markJobAsManual( wpml_tm_load_job_factory()->get_translation_job_as_active_record( $jobId ) ) : null;
	}

	private function getOriginalPostId( $trid ) {
		return Obj::prop( 'element_id', TranslationRecords::getSourceByTrid( $trid ) );
	}

	/**
	 * @param $jobId
	 * @param array $params
	 *
	 * @return array ( job_id, trid, updated_needed, language_code, post_type )
	 */
	private function get_job_data_for_restore( $jobId, array $params ) {
		$trid         = (int) filter_var( Obj::prop( 'trid', $params ), FILTER_SANITIZE_NUMBER_INT );
		$updateNeeded = (bool) filter_var( Obj::prop( 'update_needed', $params ), FILTER_SANITIZE_NUMBER_INT );
		$languageCode = (string) filter_var( Obj::prop( 'language_code', $params ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$job = null;
		if ( $jobId ) {
			$job = Jobs::get( $jobId );
		} else if ( $trid && $languageCode ) {
			$job = Jobs::getTridJob( $trid, $languageCode );
		}

		if ( $job ) {
			return [
				$jobId,
				Obj::prop( 'trid', $job ),
				Obj::prop( 'needs_update', $job ),
				Obj::prop( 'language_code', $job ),
				Obj::prop( 'original_post_type', $job )
			];
		}

		$elementType = $trid ? Obj::path( [ 0, 'element_type' ], TranslationRecords::getByTrid( $trid ) ) : null;

		return [ $jobId, $trid, $updateNeeded, $languageCode, $elementType, ];
	}

	/**
	 * @param string $sourceLangCode
	 * @param string $targetLangCode
	 * @param string $postId
	 *
	 * @return bool
	 */
	private function can_user_translate( $sourceLangCode, $targetLangCode, $postId ) {
		$args = [
			'lang_from' => $sourceLangCode,
			'lang_to'   => $targetLangCode,
			'post_id'   => $postId,
		];

		return wpml_tm_load_blog_translators()->is_translator( User::getCurrentId(), $args );
	}

	/**
	 * @param $originalPostId
	 * @param $targetLangCode
	 * @param $elementType
	 *
	 * @return \WPML_Translation_Job|null
	 */
	private function createLocalJob( $originalPostId, $targetLangCode, $elementType ) {
		$jobId = wpml_tm_load_job_factory()->create_local_job( $originalPostId, $targetLangCode, null, $elementType );

		return Maybe::fromNullable( $jobId )
		            ->map( [ wpml_tm_load_job_factory(), 'get_translation_job_as_active_record' ] )
		            ->map( $this->maybeAssignTranslator() )
		            ->map( $this->maybeSetJobStatus() )
		            ->getOrElse( null );
	}

	private function maybeAssignTranslator() {
		return function ( $jobObject ) {
			if ( $jobObject->get_translator_id() <= 0 ) {
				$jobObject->assign_to( User::getCurrentId() );
			}

			return $jobObject;
		};
	}

	private function maybeSetJobStatus() {
		return function ( $jobObject ) {
			if ( $this->isDuplicate( $jobObject ) ) {
				Jobs::setStatus( (int) $jobObject->get_id(), ICL_TM_DUPLICATE );
			} elseif ( (int) $jobObject->get_status_value() !== ICL_TM_COMPLETE ) {
				Jobs::setStatus( (int) $jobObject->get_id(), ICL_TM_IN_PROGRESS );
			}

			return $jobObject;
		};
	}

	private function markJobAsManual( $jobObject ) {
		$jobObject && Jobs::clearAutomatic( $jobObject->get_id() );

		return $jobObject;
	}

	private function isDuplicate( \WPML_Translation_Job $jobObject ) {
		return Maybe::of( $jobObject->get_original_element_id() )
		            ->map( PostTranslations::get() )
		            ->map( Obj::prop( $jobObject->get_language_code() ) )
		            ->map( Obj::prop( 'element_id' ) )
		            ->map( [ wpml_get_post_status_helper(), 'is_duplicate' ] )
		            ->getOrElse( false );
	}
}