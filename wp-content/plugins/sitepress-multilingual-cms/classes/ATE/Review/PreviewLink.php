<?php

namespace WPML\TM\ATE\Review;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\TM\API\Jobs;
use function WPML\FP\curryN;

/**
 * Class PreviewLink
 *
 * @package WPML\TM\ATE\Review
 *
 * @method static callable|string getWithSpecifiedReturnUrl( ...$returnUrl, ...$translationPostId, ...$jobId ) : Curried:: int->int->string
 * @method static callable|string get( ...$translationPostId, ...$jobId ) : Curried:: int->int->string
 * @method static callable|string getByJob( ...$job ) : Curried:: \stdClass->string
 * @method static Callable|string getNonceName( ...$translationPostId ) : Curried:: int->string
 */
class PreviewLink {
	use Macroable;

	public static function init() {

		self::macro( 'getWithSpecifiedReturnUrl', curryN( 3, function ( $returnUrl, $translationPostId, $jobId ) {
			return \add_query_arg(
				NonPublicCPTPreview::addArgs( [
					'p'                          => $translationPostId,
					'preview_id'                 => $translationPostId,
					'preview_nonce'              => \wp_create_nonce( self::getNonceName( $translationPostId ) ),
					'preview'                    => true,
					'jobId'                      => $jobId,
					'returnUrl'                  => urlencode( $returnUrl ),
				] ),
				\get_permalink( $translationPostId )
			);
		} ) );

		self::macro( 'get', curryN( 2, function ( $translationPostId, $jobId ) {
			$returnUrl = Obj::propOr( Obj::prop( 'REQUEST_URI', $_SERVER ), 'returnUrl', $_GET );

			return self::getWithSpecifiedReturnUrl( $returnUrl, $translationPostId, $jobId );
		} ) );

		self::macro( 'getByJob', curryN( 1, Fns::converge(
			self::get(),
			[
				Jobs::getTranslatedPostId(),
				Obj::prop( 'job_id' ),
			]
		) ) );

		self::macro( 'getNonceName', Str::concat( 'post_preview_' ) );
	}

}

PreviewLink::init();
