<?php

namespace WPML\TM\ATE\Review;

use WPML\API\Sanitize;
use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\TM\API\Jobs;
use function WPML\FP\curryN;

/**
 * Class PreviewLink
 *
 * @phpstan-type curried "__CURRIED_PLACEHOLDER__"
 *
 * @package WPML\TM\ATE\Review
 *
 * @method static callable|string get( ...$translationPostId, ...$jobId ) : Curried:: int->int->string
 * @method static callable|string getWithLanguagesParam( ...$languages, ...$translationPostId, ...$jobId ) : Curried:: int->int->string
 * @method static callable|string getByJob( ...$job ) : Curried:: \stdClass->string
 */
class PreviewLink {
	use Macroable;

	public static function init() {
		self::macro( 'getWithLanguagesParam', curryN( 3, function ( $languages, $translationPostId, $jobId ) {
			$returnUrl = Sanitize::string( Obj::propOr( Obj::prop( 'REQUEST_URI', $_SERVER ), 'returnUrl', $_GET ) );
			$url = self::getWithSpecifiedReturnUrl( (string) $returnUrl, $translationPostId, $jobId );

			if ( $languages ) {
				$url = \add_query_arg(['targetLanguages' => urlencode( join( ',', $languages ) ),], $url);
			}
			return $url;
		} ) );

		self::macro( 'get', curryN( 2, function ( $translationPostId, $jobId ) {
			$returnUrl = Sanitize::string( Obj::propOr( Obj::prop( 'REQUEST_URI', $_SERVER ), 'returnUrl', $_GET ) );

			return self::getWithSpecifiedReturnUrl( (string) $returnUrl, $translationPostId, $jobId );
		} ) );

		self::macro( 'getByJob', curryN( 1, Fns::converge(
			self::get(),
			[
				Jobs::getTranslatedPostId(),
				Obj::prop( 'job_id' ),
			]
		) ) );
	}

	/**
	 * @param string     $returnUrl
	 * @param string|int $translationPostId
	 * @param string|int $jobId
	 *
	 * @return string
	 *
	 * @phpstan-template V1 of string|curried
	 * @phpstan-template V2 of string|int|curried
	 * @phpstan-template V3 of object|int|curried
	 * @phpstan-template P1 of string
	 * @phpstan-template P2 of string|int
	 * @phpstan-template P3 of string|int
	 * @phpstan-template R of string
	 *
	 * @phpstan-param ?V1 $returnUrl
	 * @phpstan-param ?V2 $translationPostId
	 * @phpstan-param ?V3 $jobId
	 *
	 * @phpstan-return ($a is P1
	 *  ? ($b is P2
	 *    ? ($c is P3
	 *      ? R
	 *      : callable(P3=):R)
	 *    : ($c is P3
	 *      ? callable(P2=):R
	 *      : callable(P2=,P3=):R)
	 *  )
	 *  : ($b is P2
	 *    ? ($c is P3
	 *      ? callable(P1=):R
	 *      : callable(P1=,P3=):R)
	 *    : ($c is P3
	 *      ? callable(P1=,P2=):R
	 *      : callable(P1=,P2=,P3=):R)
	 *  )
	 * )
	 */
	public static function getWithSpecifiedReturnUrl( $returnUrl = null, $translationPostId = null, $jobId = null ) {
		$callback = function ( $returnUrl, $translationPostId, $jobId ) {
			$returnUrl         = (string) $returnUrl;
			$translationPostId = (int) $translationPostId;
			$jobId             = (int) $jobId;

			/**
			 * Returns TRUE if post_type of post is among public post type and FALSE otherwise.
			 *
			 * @param $postId
			 *
			 * @return bool
			 */
			$isPublicPostType = function ( $postId ) {
				$publicPostTypes = get_post_types( [ 'public' => true ] );
				$postType        = get_post_type( $postId );

				return in_array( $postType, $publicPostTypes, true );
			};

			$args = [
				'preview_id'    => $translationPostId,
				'preview_nonce' => \wp_create_nonce( self::getNonceName( $translationPostId ) ),
				'preview'       => true,
				'jobId'         => $jobId > 0 ? $jobId : '',
				'returnUrl'     => rawurlencode( $returnUrl ),
			];

			/**
			 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-4273
			 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1366/Translate-Everything-Incorrect-template-when-reviewing-a-translated-page
			*/
			if ( !$isPublicPostType( $translationPostId ) ) {
				// Add 'p' URL parameter only if post type isn't public
				$args['p'] = $translationPostId;
			}

			return \add_query_arg(
				NonPublicCPTPreview::addArgs( $args ),
				\get_permalink( $translationPostId )
			);
		};

		return call_user_func_array( curryN( 3, $callback ), func_get_args() );
	}

	/**
	 * @template A as string|int|curried
	 *
	 * @param A $translationPostId
	 *
	 * @return (A is curried ? callable : string)
	 */
	public static function getNonceName( $translationPostId = null ) {
		return Str::concat( 'post_preview_', $translationPostId );
	}
}

PreviewLink::init();
