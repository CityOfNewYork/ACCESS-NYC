<?php

namespace WPML\TM\ATE\Review;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Jobs;
use function WPML\Container\make;

class ApproveTranslations {

	public static function run( array $jobIds ) {
		wpml_collect( $jobIds )
			->map( Jobs::get() )
			->filter( ReviewStatus::doesJobNeedReview() )
			->map( Obj::addProp( 'translated_id', Jobs::getTranslatedPostId() ) )
			->map( Obj::props( [ 'job_id', 'translated_id' ] ) )
			->map( Lst::zipObj( [ 'jobId', 'postId' ] ) )
			->map( 'wpml_collect' )
			->map( [ make( AcceptTranslation::class ), 'run' ] );
	}
}
