<?php


namespace WPML\TM\ATE\Review;


use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\FP\curryN;
use function WPML\FP\pipe;

/**
 * Class ReviewStatus
 * @package WPML\TM\ATE\Review
 *
 * @method static callable|bool doesJobNeedReview( ...$job ) - Curried :: \stdClass->bool
 */
class ReviewStatus {
	use Macroable;

	const NEEDS_REVIEW = 'NEEDS_REVIEW';
	const EDITING = 'EDITING';
	const ACCEPTED = 'ACCEPTED';

	public static function init() {

		self::macro( 'doesJobNeedReview', curryN( 1, Logic::ifElse(
			Fns::identity(),
			pipe( Obj::prop( 'review_status' ), self::needsReview() ),
			Fns::always(false)
		) ));
	}

	/**
	 * @template A as string|curried
	 * @param A $reviewStatus
	 *
	 * @return (A is curried ? callable : bool)
	 */
	public static function needsReview( $reviewStatus = null ) {
		return Lst::includes(
			$reviewStatus ?: Fns::__,
			[
				self::NEEDS_REVIEW,
				self::EDITING,
			]
		);
	}
}

ReviewStatus::init();
