<?php

namespace WPML\TM\ATE\Review;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\Setup\Option;
use WPML\TM\API\Jobs;
use function WPML\FP\spreadArgs;

class PackageJob implements \IWPML_Backend_Action, \IWPML_REST_Action {

	const ELEMENT_TYPE_PREFIX = 'package';
	/**
	 * @return void
	 */
	public function add_hooks() {
		if ( \WPML_TM_ATE_Status::is_enabled_and_activated() && Option::shouldBeReviewed() ) {
			self::addReviewStatusHook();
			self::addSavePackageHook();
			self::addTranslationCompleteHook();
		}
	}

	/**
	 * @return void
	 */
	private static function addReviewStatusHook() {

		Hooks::onAction( 'wpml_added_translation_jobs' )
			->then(
				spreadArgs(
					function( $jobsIds ) {
						/** @var callable(object):void $setNeedsReview */
						$setNeedsReview = function( $job ) {
							if ( self::isAutomatic( $job ) ) {
								Jobs::setReviewStatus( (int) Obj::prop( 'job_id', $job ), ReviewStatus::NEEDS_REVIEW );
							}
						};

						wpml_collect( $jobsIds )
							->flatten()
							->map( Jobs::get() )
							->filter( Fns::unary( [ self::class, 'isPackageJob' ] ) )
							->map( $setNeedsReview );
					}
				)
			);
	}

	/**
	 * @return void
	 */
	private static function addSavePackageHook() {
		/** @var callable(null|string):callable $isReviewStatus */
		$isReviewStatus = Relation::propEq( 'review_status' );

		/** @var callable(object):bool $mightNeedReview */
		$mightNeedReview = Logic::anyPass(
			[
				$isReviewStatus( ReviewStatus::ACCEPTED ),     // a previous job was completed.
				$isReviewStatus( ReviewStatus::NEEDS_REVIEW ), // a previous job needs update.
				$isReviewStatus( null ),                       // new job.
			]
		);

		/** @var callable(bool, string, object):bool $shouldSaveStringPackageTranslation */
		$shouldSaveStringPackageTranslation = function( $shouldSave, $typePrefix, $job ) use ( $mightNeedReview ) {
			if ( self::ELEMENT_TYPE_PREFIX === $typePrefix && self::isAutomatic( $job ) && $mightNeedReview( $job )
				&& Option::HOLD_FOR_REVIEW === Option::getReviewMode()
			) {
				return false;
			}

			return $shouldSave;
		};

		Hooks::onFilter( 'wpml_should_save_external', 10, 3 )
			->then( spreadArgs( $shouldSaveStringPackageTranslation ) );
	}

	/**
	 * @return void
	 */
	private static function addTranslationCompleteHook() {
		$setPackageTranslationStatus = function( $new_post_id, $fields, $job ) {
			if ( ! self::isPackageJob( $job ) ) {
				return;
			}

			if ( Relation::propEq( 'review_status', ReviewStatus::EDITING, $job ) ) {
				Jobs::setReviewStatus( (int) $job->job_id, ReviewStatus::ACCEPTED );
			}
		};
		Hooks::onAction( 'wpml_pro_translation_completed', 10, 3 )
			->then( spreadArgs( $setPackageTranslationStatus ) );
	}

	/**
	 * @param object $job
	 *
	 * @return bool
	 */
	public static function isPackageJob( $job ) {
		return Relation::propEq( 'element_type_prefix', self::ELEMENT_TYPE_PREFIX, $job );
	}

	/**
	 * @param object $job
	 *
	 * @return bool
	 */
	private static function isAutomatic( $job ) {
		return (bool) Obj::prop( 'automatic', $job );
	}
}
