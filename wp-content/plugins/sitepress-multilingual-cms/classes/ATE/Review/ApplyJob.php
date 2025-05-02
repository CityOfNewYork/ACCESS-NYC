<?php

namespace WPML\TM\ATE\Review;

use WPML\Element\API\Post as WPMLPost;
use WPML\FP\Fns;
use WPML\FP\Str;
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

	/**
	 * It sets "review_status" to "NEEDS_REVIEW" when the job is completed and it should be reviewed.
	 *
	 * @return void
	 */
	private static function addJobStatusHook() {
		$applyReviewStatus = function ( $status, $job ) {
			if (
				self::shouldBeReviewed( $job )
				&& $status === ICL_TM_COMPLETE
			) {
				Jobs::setReviewStatus(
					(int) $job->job_id,
                    ReviewStatus::NEEDS_REVIEW
                );
			}

			return $status;
		};

		Hooks::onFilter( 'wpml_tm_applied_job_status', 10, 2 )
		     ->then( spreadArgs( $applyReviewStatus ) );
	}

	/**
	 * It sets the post status to "draft" when a new post is created and it should be reviewed.
	 *
	 * @return void
	 */
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

	/**
	 * It ensures that a draft post remains as draft after edition.
     *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-8512
	 *
	 * @return void
	 */
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
		$isAutomatic = Obj::prop( 'automatic', $job );
		if ( ! $isAutomatic ) {
			return false;
		}

		$originalElementId = (int) Obj::prop( 'original_doc_id', $job );
		$isHomePage        = $originalElementId && $originalElementId == (int) get_option( 'page_for_posts' );

		if ( $isHomePage ) {
			return false;
		}

		$excluded = apply_filters(
			'wpml_tm_skip_element_type_from_review',
			self::excludeElementTypes( $job ),
			$job->original_post_type
		);

		if ( $excluded ) {
			return false;
		}

		if ( ! property_exists( $job, 'completed_date' ) || ! $job->completed_date ) {
			return true;
		}

		// Only set to review if it's a new job (not older than 60 seconds) and not
		// a retranslation like it happens for glossary updates or formality changes.
		// A 60 seconds timeframe is needed as the job is marked as completed before
		// this shouldBeReviewed function is called.
		return time() - strtotime( $job->completed_date ) < 60;

	}

	/**
	 * @param object $job
	 *
	 * @return bool
	 */
	private static function excludeElementTypes( $job ): bool {
		/** @var string $elementType e.g "post_post", "post_page", "post_attachment", "post_nav_menu_item", "package_gravityforms", "st-batch_strings" */
		$elementType =Obj::prop( 'original_post_type', $job );

		/**
		 * We exclude packages here because they are handled in a separate class: PackageJob.
		 */
		if ( Str::startsWith( 'st-batch', $elementType ) || Str::startsWith( 'package', $elementType ) ) {
			return true;
		}

		return false;
	}
}
