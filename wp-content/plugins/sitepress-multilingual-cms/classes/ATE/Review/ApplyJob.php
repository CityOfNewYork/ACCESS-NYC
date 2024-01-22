<?php

namespace WPML\TM\ATE\Review;

use WPML\Element\API\Post as WPMLPost;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use WPML\Setup\Option;
use WPML\TM\API\Jobs;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class ApplyJob implements \IWPML_Backend_Action, \IWPML_REST_Action, \IWPML_AJAX_Action {

	/** @var string[] */
	private static $excluded_from_review = [ 'st-batch', 'package' ];

	public function add_hooks() {
		if ( \WPML_TM_ATE_Status::is_enabled_and_activated() && Option::shouldBeReviewed() ) {
			self::addJobStatusHook();
			self::addTranslationCompleteHook();
			self::addTranslationPreSaveHook();
		}
	}

	private static function addJobStatusHook() {
		$applyReviewStatus = function ( $status, $job ) {
			if (
				self::shouldBeReviewed( $job )
				&& $status === ICL_TM_COMPLETE
			) {
				Jobs::setReviewStatus(
					(int) $job->job_id,
					ReviewStatus::NEEDS_REVIEW );
			}

			return $status;
		};

		Hooks::onFilter( 'wpml_tm_applied_job_status', 10, 2 )
		     ->then( spreadArgs( $applyReviewStatus ) );
	}

	private static function addTranslationCompleteHook() {
		$isHoldToReviewMode = Fns::always( Option::getReviewMode() === 'before-publish' );

		$shouldTranslationBeReviewed = function ( $translatedPostId ) {
			$job = Jobs::getPostJob( $translatedPostId, Post::getType( $translatedPostId ), WPMLPost::getLang( $translatedPostId ) );

			return $job && self::shouldBeReviewed( $job );
		};

		$isPostNewlyCreated = Fns::converge( Relation::equals(), [
			Obj::prop( 'post_date' ),
			Obj::prop( 'post_modified' )
		] );

		/** @var callable $isNotNull */
		$isNotNull = Logic::isNotNull();

		$setPostStatus = pipe(
			Maybe::of(),
			Fns::filter( $isHoldToReviewMode ),
			Fns::filter( $shouldTranslationBeReviewed ),
			Fns::map( Post::get() ),
			Fns::filter( $isNotNull ),
			Fns::filter( $isPostNewlyCreated ),
			Fns::map( Obj::prop( 'ID' ) ),
			Fns::map( Post::setStatus( Fns::__, 'draft' ) )
		);

		Hooks::onAction( 'wpml_pro_translation_completed' )
		     ->then( spreadArgs( $setPostStatus ) );
	}

	private static function addTranslationPreSaveHook() {
		$keepDraftPostsDraftIfNeedsReview = function ( $postArr, $job ) {
			if (
				self::shouldBeReviewed( $job )
				&& isset( $postArr['ID'] )
				&& get_post_status( $postArr['ID'] ) === 'draft'
			) {
				$postArr['post_status'] = 'draft';
			}

			return $postArr;
		};
		Hooks::onFilter( 'wpml_pre_save_pro_translation', 10, 2 )
		     ->then( spreadArgs( $keepDraftPostsDraftIfNeedsReview ) );
	}

	/**
	 * @param $job
	 *
	 * @return bool
	 */
	private static function shouldBeReviewed( $job ) {
		return ! Lst::includes( $job->element_type_prefix, self::$excluded_from_review )
		       && $job->automatic
		       && (int) $job->original_doc_id !== (int) get_option( 'page_for_posts' );
	}


}
