<?php

namespace WPML\TM\AutomaticTranslation\Actions;

use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Debug;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use WPML\Settings\PostType\Automatic;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\API\Job\Map;
use function WPML\Container\make;
use function WPML\FP\invoke;
use WPML\LIB\WP\User;
use WPML\Setup\Option;
use WPML\Infrastructure\WordPress\Component\StringPackage\Application\Query\PackageDefinitionQuery;

use function WPML\FP\partial;
use WPML\TM\API\Jobs;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class Actions implements \IWPML_Action {

	/** @see \WPML\PB\Shutdown\Hooks */
	const PRIORITY_AFTER_PB_PROCESS = 100;

	/** @var \WPML_Translation_Element_Factory */
	private $translationElementFactory;

  /** @var PackageDefinitionQuery */
  private $packageDefinitionQuery;

	public function __construct(
		\WPML_Translation_Element_Factory $translationElementFactory,
    $packageDefinitionQuery = null
	) {
		$this->translationElementFactory = $translationElementFactory;
    $this->packageDefinitionQuery = $packageDefinitionQuery ?: new PackageDefinitionQuery();
	}

	public function add_hooks() {
		Hooks::onAction( 'wpml_after_save_post', 100 )
		     ->then( spreadArgs( Fns::memorize( [ $this, 'sendToTranslation' ] ) ) );
		Hooks::onAction( 'wpml_st_package_string_registered' )
			->then( spreadArgs( [ $this, 'sendPackageToTranslation' ] ) );
	}

	/**
	 * @param int $postId
	 * @param callable|null $onComplete
	 *
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function sendToTranslation( $postId, $onComplete = null ) {
		$execOnComplete = function () use ( $postId, $onComplete ) {
			if ( is_callable( $onComplete ) ) {
				$onComplete( $postId );
			}
		};

		if ( empty( $_POST['icl_minor_edit'] ) ) {
			$postElement = $this->translationElementFactory->create_post( $postId );
			if ( $postElement->is_translatable() && Automatic::isAutomatic( $postElement->get_type() ) ) {
				Hooks::onAction( 'shutdown', self::PRIORITY_AFTER_PB_PROCESS )
				     ->then( function () use ( $postId, $execOnComplete ) {
					     $postElement = $this->translationElementFactory->create_post( $postId );
						 $postStatus  = $postElement->get_wp_object()->post_status;
					     if (
							(
								'publish' === $postStatus
								|| (
									'draft' === $postStatus
									&& \WPML\Setup\Option::getTranslateEverythingDrafts()
								)
							)
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

							 // When $secondaryLanguageCodes is empty this can mean that WPML failed to get languages eligible for automatic translation
						     // from ATE side, and if so, we display notice to the user that WPML failed to create translation jobs for the newly created post/page
						     if ( ! Lst::length( $secondaryLanguageCodes ) ) {
							     do_action( 'wpml_update_failed_jobs_notice', $postElement );
						     }

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
	 * @param \WPML_Package $package
	 *
	 * @return void
	 */
	public function sendPackageToTranslation( $package ) {
		static $updatedPackages = [];

		if ( ! $package || ! Obj::prop( 'ID', $package ) ) {
			return;
		}

		if ( isset( $updatedPackages[ $package->ID ] ) ) {
			return;
		}

		$updatedPackages[ $package->ID ] = true;

    $shouldTranslate = $this->packageDefinitionQuery->isPackageOnTheList( $package->kind_slug );

		/**
		 * Allows enabling automatic translation for string packages.
		 *
		 * @since 4.7.0
		 *
		 * @param bool  $shouldTranslate
		 * @param array $packageData {
		 *     @type string $name
		 *     @type string $kind
		 *     @type string $kind_slug
		 * }
		 */
		if ( apply_filters( 'wpml_auto_translate_string_package', $shouldTranslate, (array) $package ) ) {
			Hooks::onAction( 'shutdown' )
				->then( $this->getPackageHandler( $package ) );
		}
	}

	/**
	 * @param \WPML_Package $package
	 *
	 * @return \Closure
	 */
	private function getPackageHandler( \WPML_Package $package ) {
		return function() use ( $package ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$packageElement = $this->translationElementFactory->create_package( $package->ID, $package->kind_slug );

			if (
				$packageElement->get_language_code() === Languages::getDefaultCode()
				&& $packageElement->get_source_language_code() === null
			) {
				$secondaryLanguageCodes = LanguageMappings::geCodesEligibleForAutomaticTranslations();

				// @todo: When $secondaryLanguageCodes is empty this can mean that WPML failed to get languages eligible for automatic translation
				// from ATE side, we should warn the user as we do for posts.

				$this->cancelExistingTranslationJobs( $packageElement, $secondaryLanguageCodes );
				$this->createTranslationJobs( $packageElement, $secondaryLanguageCodes );
			}
		};
	}

	/**
	 * @param \WPML_Translation_Element $translationElement
	 * @param array                     $languages
	 */
	private function cancelExistingTranslationJobs( \WPML_Translation_Element $translationElement, $languages ) {
		$getJobEntity = function ( $jobId ) use ( $translationElement ) {
			return wpml_tm_get_jobs_repository()->get_job( Map::fromJobId( $jobId ), $translationElement->get_element_type() );
		};

		wpml_collect( $languages )
			->map( Jobs::getElementJob( $translationElement->get_element_id(), $translationElement->get_wpml_element_type() ) )
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

	/**
	 * @param \WPML_Translation_Element $translationElement
	 * @param array                     $targetLanguages
	 *
	 * @return void
	 */
	public function createTranslationJobs( \WPML_Translation_Element $translationElement, $targetLanguages ) {
		if ( ! Option::shouldTranslateEverything() ) {
			return;
		}

		$isNotCompleteAndUpToDate = Logic::complement( self::isCompleteAndUpToDateJob() );

		$sendToTranslation = function ( $language ) use ( $translationElement, $isNotCompleteAndUpToDate ) {
			/** @var \stdClass|false $job */
			$job = Jobs::getElementJob( $translationElement->get_element_id(), $translationElement->get_wpml_element_type(), $language );

			if (
				! $job
				|| (
					$isNotCompleteAndUpToDate( $job )
					&& $this->canJobBeReTranslatedAutomatically( $job->job_id )
				)
			) {
				$this->createJob( $translationElement, $language );
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
	 * @param \WPML_Translation_Element $translationElement
	 * @param string                    $language
	 */
	private function createJob( \WPML_Translation_Element $translationElement, $language ) {
		$batch = new \WPML_TM_Translation_Batch(
			[
				new \WPML_TM_Translation_Batch_Element(
					$translationElement->get_element_id(),
					$translationElement->get_element_type(),
					$translationElement->get_language_code(),
					[ $language => 1 ]
				),
			],
			\TranslationProxy_Batch::get_generic_batch_name( true ),
			[ $language => User::getCurrentId() ]
		);

		wpml_load_core_tm()->send_jobs( $batch, $translationElement->get_element_type(), Jobs::SENT_AUTOMATICALLY );
	}


	/**
	 * @param string $sourceLanguage
	 * @param array  $elements E.g. [ [1, 'fr'], [1, 'de'], [2, 'fr'] ]
	 * @param string $elementType Element type 'post_page' or 'package_wpforms' or 'st-batch'
	 *
	 * @return array
	 */
	public function createNewTranslationJobs( $sourceLanguage, array $elements, $elementType ) {
		$getTargetLang      = Lst::nth( 1 );
		$setTranslateAction = Obj::objOf( Fns::__, \TranslationManagement::TRANSLATE_ELEMENT_ACTION );
		$setTranslatorId    = Obj::objOf( Fns::__, User::getCurrentId() );

		$wpmlType = 'post';
		if ( $elementType === 'st-batch' ) {
			$wpmlType = 'st-batch';
		} else if ( Str::startsWith( 'package_', $elementType ) ) {
			$wpmlType = 'package';
		}

		$targetLanguages = \wpml_collect( $elements )
			->map( $getTargetLang )
			->unique()
			->mapWithKeys( $setTranslatorId )
			->toArray();

		$makeBatchElement = function ( $targetLanguages, $postId ) use ( $sourceLanguage, $wpmlType ) {
			return new \WPML_TM_Translation_Batch_Element(
				$postId,
				$wpmlType,
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
		$batch->setTranslationMode( 'auto' );

		wpml_load_core_tm()->send_jobs( $batch, $wpmlType, Jobs::SENT_AUTOMATICALLY );

		$getJobId = pipe(
			Fns::converge( Jobs::getElementJob(), [
				Obj::prop( 'elementId' ),
				Obj::prop( 'elementType' ),
				Obj::prop( 'lang' )
			] ),
			Obj::prop( 'job_id' ),
			Fns::unary( 'intval' )
		);

		return \wpml_collect( $elements )
			->map( Lst::zipObj( [ 'elementId', 'lang' ] ) )
			->map( Obj::addProp( 'elementType', Fns::always( $elementType === 'st-batch' ? 'st-batch_strings' : $elementType ) ) )
			->map( Obj::addProp( 'jobId', $getJobId ) )
			->toArray();
	}
}
