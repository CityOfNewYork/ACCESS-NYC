<?php

namespace WPML\TM\ATE\Review;

use WPML\API\Sanitize;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use WPML\Element\API\Post as WPMLPost;
use WPML\TM\API\Jobs;
use WPML\TM\API\Translators;
use WPML\Core\WP\App\Resources;
use WPML\FP\Obj;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class ReviewTranslation implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		if ( self::hasValidNonce() ) {
			Hooks::onFilter( 'query_vars' )
			     ->then( spreadArgs( NonPublicCPTPreview::allowReviewPostTypeQueryVar() ) );

			Hooks::onFilter( 'request' )
			     ->then( spreadArgs( NonPublicCPTPreview::enforceReviewPostTypeIfSet() ) );

			Hooks::onFilter( 'the_preview' )
			     ->then( Hooks::getArgs( [ 0 => 'post' ] ) )
			     ->then( $this->handleTranslationReview() );

			if ( $this->isCurrentPageReview() ) {
				add_filter(
					'init',
					function () {
						// This hook is only needed for the WP Autosaved revision preview.
						// For Translation Review it can cause problems overwritting the post by an autosaved draft.
						remove_filter( 'the_preview', '_set_preview' );
						return true;
					},
					PHP_INT_MAX
				);
			}
		}

		Hooks::onFilter( 'user_has_cap', 10, 3 )
		     ->then( spreadArgs( function ( $userCaps, $requiredCaps, $args ) {
				 /** @var array $userCaps */
				 /** @var array $requiredCaps */
				 /** @var array $args */

			     if ( Relation::propEq( 0, 'edit_post', $args ) ) {
				     $translator = Translators::getCurrent();

				     if ( $translator->ID ) {
					     $postId = $args[2];
					     $job    = Jobs::getPostJob( $postId, Post::getType( $postId ), WPMLPost::getLang( $postId ) );

					     if ( ReviewStatus::doesJobNeedReview( $job ) && self::canEditLanguage( $translator, $job ) ) {
						     return Lst::concat( $userCaps, Lst::zipObj( $requiredCaps, Lst::repeat( true, count( $requiredCaps ) ) ) );
					     }
				     }

				     return $userCaps;
			     }

			     return $userCaps;
		     } ) );

		Hooks::onFilter( 'wpml_tm_allowed_translators_for_job', 10, 2 )
		     ->then( spreadArgs( function ( $allowedTranslators, \WPML_Element_Translation_Job $job ) {
			     $job        = $job->to_array();
			     $translator = Translators::getCurrent();

			     if ( ReviewStatus::doesJobNeedReview( $job ) && self::canEditLanguage( $translator, $job ) ) {
				     return array_merge( $allowedTranslators, [ $translator->ID ] );
			     }

			     return $allowedTranslators;
		     } ) );

		if ( $this->isCurrentPageReviewPostTypeTemplate() ) {
			Hooks::onFilter( 'pre_render_block', 10, 2 )->then( spreadArgs( [ $this, 'onPreRenderBlock' ] ) );
		}
	}

	private static function canEditLanguage( $translator, $job ) {
		if ( ! $job ) {
			return false;
		}

		return Lst::includes( Obj::prop('language_code', $job), Obj::pathOr( [], [ 'language_pairs', Obj::prop('source_language_code', $job) ], $translator ) );
	}

	/**
	 * This will ensure to block the standard preview
	 * for non-public CPTs.
	 *
	 * @return bool
	 */
	private static function hasValidNonce() {
		$get = Obj::prop( Fns::__, $_GET );

		return (bool) \wp_verify_nonce(
			$get( 'preview_nonce' ),
			PreviewLink::getNonceName( (int) $get( 'preview_id' ) )
		);
	}

	/**
	 * @param int $jobId
	 *
	 * @return callable
	 */
	public function handleTranslationReview() {
		return function ( $data ) {
			$post  = Obj::prop( 'post', $data );
			$jobId = filter_input( INPUT_GET, 'jobId', FILTER_SANITIZE_NUMBER_INT );
			$filterTargetLanguages = Sanitize::stringProp('targetLanguages', $_GET)
				? Str::split( ',', Sanitize::stringProp('targetLanguages', $_GET) ) : null;

			if ( $jobId ) {
				/**
				 * This hooks is fired as soon as a translation review is about to be displayed.
				 *
				 * @since 4.5.0
				 *
				 * @param int             $jobId The job Id.
				 * @param object|\WP_Post $post  The job's related object to be reviewed.
				 */
				do_action( 'wpml_tm_handle_translation_review', $jobId, $post );

				Hooks::onFilter( 'wp_redirect' )
					 ->then( [ __CLASS__, 'failGracefullyOnPreviewRedirection' ] );

				Hooks::onAction( 'template_redirect', PHP_INT_MAX )
					 ->then( function () {
						 Hooks::onAction( 'wp_footer' )
							  ->then( [ __CLASS__, 'printReviewToolbarAnchor' ] );
					 } );

				show_admin_bar( false );

				$enqueue = Resources::enqueueApp( 'translationReview' );
				$enqueue( $this->getData( $jobId, $post, $filterTargetLanguages ) );
			}

			return $post;
		};
	}

	public static function printReviewToolbarAnchor() {
		echo '
			<script type="text/javascript" >
			   var ajaxurl = "' . \admin_url( 'admin-ajax.php', 'relative' ) . '"
			</script>
			<div id="wpml_translation_review"></div>
		';
	}

	/**
	 * @return null This will stop the redirection.
	 */
	public static function failGracefullyOnPreviewRedirection() {
		do_action( 'wp_head' );
		self::printReviewToolbarAnchor();

        echo '
            <div class="wpml-review__modal-mask wpml-review__modal-mask-transparent">
                <div class="wpml-review__modal-box wpml-review__modal-box-transparent wpml-review__modal-preview-not-available">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 0C10.8 0 0 10.8 0 24C0 37.2 10.8 48 24 48C37.2 48 48 37.2 48 24C48 10.8 37.2 0 24 0ZM24 43.5C13.2 43.5 4.5 34.8 4.5 24C4.5 13.2 13.2 4.5 24 4.5C34.8 4.5 43.5 13.2 43.5 24C43.5 34.8 34.8 43.5 24 43.5Z" fill="url(#paint0_linear)"/><path d="M24 10.2C22.5 10.2 21 11.4 21 13.2C21 15 22.2 16.2 24 16.2C25.8 16.2 27 15 27 13.2C27 11.4 25.5 10.2 24 10.2ZM24 20.4C22.8 20.4 21.9 21.3 21.9 22.5V35.7C21.9 36.9 22.8 37.8 24 37.8C25.2 37.8 26.1 36.9 26.1 35.7V22.5C26.1 21.3 25.2 20.4 24 20.4Z" fill="url(#paint1_linear)"/><defs><linearGradient id="paint0_linear" x1="38.6667" y1="6.66666" x2="8" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#27AD95"/><stop offset="1" stop-color="#2782AD"/></linearGradient><linearGradient id="paint1_linear" x1="38.6667" y1="6.66666" x2="8" y2="48" gradientUnits="userSpaceOnUse"><stop stop-color="#27AD95"/><stop offset="1" stop-color="#2782AD"/></linearGradient></defs></svg>
                    <h2>'. esc_html__( 'Preview is not available', 'wpml-translation-management' ) .'</h2>
                    <p>'. sprintf(esc_html__( 'Click %sEdit Translation%s in the toolbar above to review your translation in the editor.', 'wpml-translation-management' ), '<strong>', '</strong>') .'</p>
                </div>
            </div>
        ';

		return null;
	}


	public function getData( $jobId, $post, $filterTargetLanguages ) {
		$job = Jobs::get( $jobId );

		return [
			'name' => 'reviewTranslation',
			'data' => [
				'jobEditUrl'          => $this->getEditUrl( $jobId ),
				'nextJobUrl'          => NextTranslationLink::get( $job, $filterTargetLanguages ),
				'jobId'               => (int) $jobId,
				'postId'              => $post->ID,
				'isPublished'         => Relation::propEq( 'post_status', 'publish', $post ) ? 1 : 0,
				'needsReview'         => ReviewStatus::doesJobNeedReview( $job ),
				'completedInATE'      => $this->isCompletedInATE( $_GET ),
				'isReturningFromATE'  => (bool) Obj::prop( 'editFromReviewPage', $_GET ),
				'clickedBackInATE'    => (bool) Obj::prop( 'back', $_GET ),
				'needsUpdate'         => is_object( $job ) ? Relation::propEq( 'review_status', ReviewStatus::EDITING, $job ) : false,
				'previousTranslation' => Sanitize::stringProp( 'previousTranslation', $_GET ),
				'backUrl'             => Obj::prop( 'returnUrl', $_GET ),
				'endpoints'           => [
					'accept' => AcceptTranslation::class,
					'update' => UpdateTranslation::class
				],
			]
		];
	}

	/**
	 * Returns completed status based on key 'complete_no_changes' in $params.
	 * Returns NOT_COMPLETED if 'complete_no_changes' is not set.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function isCompletedInATE( $params ) {
		$completedInATE = pipe(
			Obj::prop( 'complete_no_changes' ),
			'strval',
			Logic::cond( [
				[ Relation::equals( '1' ), Fns::always( 'COMPLETED_WITHOUT_CHANGED' ) ],
				[ Relation::equals( '0' ), Fns::always( 'COMPLETED' ) ],
				[ Fns::always( true ), Fns::always( 'NOT_COMPLETED' ) ],
			] )
		);

		return $completedInATE( $params );
	}

	/**
	 * @param int $jobId
	 *
	 * @return string
	 */
	private function getEditUrl( $jobId ) {
		return \add_query_arg( [ 'preview' => 1 ], Jobs::getEditUrl( $this->getReturnParamInEditUrl(), $jobId ) );
	}

	/**
	 * @return string
	 */
	private function getReturnParamInEditUrl() {
		/**
		 * Those are GET params which ATE may send when we return to the review page.
		 * We don't want to include them in subsequent edit link.
		 */
		$ateParams = [
			'back',
			'complete_no_changes',
			'ate_status',
			'complete',
			'in_progress',
			'ate_original_id',
			'ate_status',
			'editFromReviewPage',
		];

		$returnParam = Jobs::getCurrentUrl();
		$returnParam = \remove_query_arg( $ateParams, $returnParam );

		/**
		 * We need to add the `editFromReviewPage` param to the return URL to be able to detect that we are returning from ATE to the review page.
		 * It is used to repeat the sync on "in-progress" status.
		 */
		$returnParam = \add_query_arg( [ 'editFromReviewPage' => 1 ], $returnParam );

		return $returnParam;
	}

	/**
	 * @return boolean
	 */
	public function isCurrentPageReviewPostTypeTemplate() {
		$queryVars = [];
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $queryVars );
		}

		return Obj::has( 'wpmlReviewPostType', $queryVars ) && 'wp_template' === $queryVars['wpmlReviewPostType'];
	}

	/**
	 * @return boolean
	 */
	public function isCurrentPageReview() {
		$queryVars = [];
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $queryVars );
		}

		$jobId = filter_input( INPUT_GET, 'jobId', FILTER_SANITIZE_NUMBER_INT );
		return !! $jobId || Obj::has( 'wpmlReviewPostType', $queryVars );
	}

	/**
	 * This filter is called from WP core /wp-includes/blocks.php right before block is rendered.
	 * If anything other than null is returned from this filter that value is used as final block rendered value without calling actual block render function.
	 *
	 * @param string|null $preRenderedContent Pre-rendered context for the block.
	 * @param array       $blockParams Block params being rendered.
	 *
	 * @return string|null $context
	 */
	public function onPreRenderBlock( $preRenderedContent, $blockParams ) {
		// Fixes error 'postId is not defined' in WP core when context vars are removed for posts with 'wp_template' type.
		if ( is_array( $blockParams ) && 'core/comments' === $blockParams['blockName'] && $this->isCurrentPageReviewPostTypeTemplate() ) {
			return '';
		}
	}
}
