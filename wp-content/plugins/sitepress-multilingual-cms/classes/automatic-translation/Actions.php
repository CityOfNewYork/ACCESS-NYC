<?php

namespace WPML\TM\AutomaticTranslation\Actions;

use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use WPML\Settings\PostType\Automatic;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\API\Job\Map;
use function WPML\FP\invoke;
use WPML\LIB\WP\User;
use WPML\Setup\Option;

use function WPML\FP\partial;
use WPML\TM\API\Jobs;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class Actions implements \IWPML_Action {

	/** @see \WPML\PB\Shutdown\Hooks */
	const PRIORITY_AFTER_PB_PROCESS = 100;

	/** @var \WPML_Translation_Element_Factory */
	private $translationElementFactory;

	public function __construct(
		\WPML_Translation_Element_Factory $translationElementFactory
	) {
		$this->translationElementFactory = $translationElementFactory;
	}

	public function add_hooks() {
		Hooks::onAction( 'wpml_after_save_post', 100 )
		     ->then( spreadArgs( Fns::memorize( [ $this, 'sendToTranslation' ] ) ) );
	}

	/**
	 * @param int           $postId
	 * @param callable|null $onComplete
	 *
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function sendToTranslation( $postId, $onComplete = null ) {
		$execOnComplete = function() use ( $postId, $onComplete ) {
			if ( is_callable( $onComplete ) ) {
				$onComplete( $postId );
			}
		};

		if ( empty( $_POST['icl_minor_edit'] ) ) {
			$postElement = $this->translationElementFactory->create_post( $postId );
			if (
				$postElement->is_translatable()
				&& ! $postElement->is_display_as_translated()
				&& Automatic::isAutomatic( $postElement->get_type() )
			) {
				Hooks::onAction( 'shutdown', self::PRIORITY_AFTER_PB_PROCESS )
				     ->then( function() use ( $postId, $execOnComplete ) {
					     $postElement = $this->translationElementFactory->create_post( $postId );
					     if (
						     $postElement->get_wp_object()->post_status === 'publish'
						     && $postElement->get_language_code() === Languages::getDefaultCode()
						     && $postElement->get_source_language_code() === null
						     /**
						      * Allows excluding some posts from automatic translation.
						      *
						      * @param bool false   Is the post excluded.
						      * @param int  $postId The post ID to check.
						      */
						     && ! apply_filters( 'wpml_exclude_post_from_auto_translate', false, $postId )
					     ) {
						     $secondaryLanguageCodes = LanguageMappings::geCodesEligibleForAutomaticTranslations();

						     $this->cancelExistingTranslationJobs( $postElement, $secondaryLanguageCodes );
						     $this->createTranslationJobs( $postElement, $secondaryLanguageCodes );
					     }

						 $execOnComplete();
				     } );
			} else {
				$execOnComplete();
			}
		} else {
			$execOnComplete();
		}
	}

	/**
	 * @param \WPML_Post_Element $postElement
	 * @param array              $languages
	 *
	 * @throws \WPML\Auryn\InjectionException
	 */
	private function cancelExistingTranslationJobs( \WPML_Post_Element $postElement, $languages ) {
		$getJobEntity = function ( $jobId ) {
			return wpml_tm_get_jobs_repository()->get_job( Map::fromJobId( $jobId ), \WPML_TM_Job_Entity::POST_TYPE );
		};

		wpml_collect( $languages )
			->map( Jobs::getPostJob( $postElement->get_element_id(), $postElement->get_type() ) )
			->filter()
			->reject( self::isCompleteAndUpToDateJob() )
			->map( Obj::prop( 'job_id' ) )
			->map( Jobs::clearReviewStatus() )
			->map( Jobs::setNotTranslatedStatus() )
			->map( Jobs::clearTranslated() )
			->map( $getJobEntity )
			->map( Fns::tap( partial( 'do_action', 'wpml_tm_job_cancelled' ) ) );
	}

	/**
	 * @return callable :: \stdClass -> bool
	 */
	private static function isCompleteAndUpToDateJob() {
		return function ( $job ) {
			return Cast::toInt( $job->needs_update ) !== 1 && Cast::toInt( $job->status ) === ICL_TM_COMPLETE;
		};
	}

	public function createTranslationJobs( \WPML_Post_Element $postElement, $targetLanguages ) {
		if ( Option::isPausedTranslateEverything() ) {
			return;
		}

		$isNotCompleteAndUpToDate = Logic::complement( self::isCompleteAndUpToDateJob() );

		$sendToTranslation = function ( $language ) use ( $postElement, $isNotCompleteAndUpToDate ) {
			/** @var \stdClass|false $job */
			$job = Jobs::getPostJob( $postElement->get_element_id(), $postElement->get_type(), $language );

			if (
				! $job
				|| (
					$isNotCompleteAndUpToDate( $job )
					&& $this->canJobBeReTranslatedAutomatically( $job->job_id )
				)
			) {
				$this->createJob( $postElement, $language );
			}
		};

		Fns::map( $sendToTranslation, $targetLanguages );
	}

	/**
	 * @param int $jobId
	 *
	 * @return bool
	 */
	private function canJobBeReTranslatedAutomatically( $jobId ) {
		return wpml_tm_load_old_jobs_editor()->get( $jobId ) === \WPML_TM_Editors::ATE;
	}

	/**
	 * @param \WPML_Post_Element $postElement
	 * @param string             $language
	 */
	private function createJob( \WPML_Post_Element $postElement, $language ) {
		$batch = new \WPML_TM_Translation_Batch(
			[
				new \WPML_TM_Translation_Batch_Element(
					$postElement->get_element_id(),
					'post',
					$postElement->get_language_code(),
					[ $language => 1 ]
				),
			],
			\TranslationProxy_Batch::get_generic_batch_name( true ),
			[ $language => User::getCurrentId() ]
		);

		wpml_load_core_tm()->send_jobs( $batch, 'post', Jobs::SENT_AUTOMATICALLY );
	}


	/**
	 * @param       $sourceLanguage
	 * @param array $elements E.g. [ [1, 'fr'], [1, 'de'], [2, 'fr'] ]
	 */
	public function createNewTranslationJobs( $sourceLanguage, array $elements ) {
		$getTargetLang      = Lst::nth( 1 );
		$setTranslateAction = Obj::objOf( Fns::__, \TranslationManagement::TRANSLATE_ELEMENT_ACTION );
		$setTranslatorId    = Obj::objOf( Fns::__, User::getCurrentId() );

		$targetLanguages = \wpml_collect( $elements )
			->map( $getTargetLang )
			->unique()
			->mapWithKeys( $setTranslatorId )
			->toArray();

		$makeBatchElement = function ( $targetLanguages, $postId ) use ( $sourceLanguage ) {
			return new \WPML_TM_Translation_Batch_Element(
				$postId,
				'post',
				$sourceLanguage,
				$targetLanguages->toArray()
			);
		};

		$batchElements = \wpml_collect( $elements )
			->groupBy( 0 )
			->map( Fns::map( $getTargetLang ) )
			->map( invoke( 'mapWithKeys' )->with( $setTranslateAction ) )
			->map( $makeBatchElement )
			->values()
			->toArray();

		$batch = new \WPML_TM_Translation_Batch(
			$batchElements,
			\TranslationProxy_Batch::get_generic_batch_name( true ),
			$targetLanguages
		);

		wpml_load_core_tm()->send_jobs( $batch, 'post', Jobs::SENT_AUTOMATICALLY );

		$getJobId = pipe(
			Fns::converge( Jobs::getPostJob(), [
				Obj::prop( 'postId' ),
				Obj::prop( 'postType' ),
				Obj::prop( 'lang' )
			] ),
			Obj::prop( 'job_id' ),
			Fns::unary( 'intval' )
		);

		return \wpml_collect( $elements )
			->map( Lst::zipObj( [ 'postId', 'lang' ] ) )
			->map( Obj::addProp( 'postType', pipe( Obj::prop( 'postId' ), Post::getType() ) ) )
			->map( Obj::addProp( 'jobId', $getJobId ) )
			->toArray();
	}
}
