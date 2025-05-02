<?php

namespace WPML\TM\ATE\Review;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\API\Jobs;
use function WPML\Container\make;

class ApproveTranslations implements IHandler {

	public function run( Collection $data ) {
		$jobIds           = $data->get( 'jobsIds' );
		$TranslatedPostId = function ( $arg ) {
			// we need to check if it's package type, otherwise this will return a misleading ID.
			if ( PackageJob::isPackageJob( $arg ) ) {
				return null;
			}
			return Jobs::getTranslatedPostId()( $arg );
		};

		return wpml_collect( $jobIds )
			->map( Jobs::get() )
			->filter( ReviewStatus::doesJobNeedReview() )
			->map( Obj::addProp( 'translated_id', $TranslatedPostId ) )
			->map( Obj::props( [ 'job_id', 'translated_id', 'element_type_prefix' ] ) )
			->map( Lst::zipObj( [ 'jobId', 'postId', 'element_type_prefix' ] ) )
			->map( 'wpml_collect' )
			->map( [ make( AcceptTranslation::class ), 'run' ] )
			->map(
                function ( Either $result ) {
                    $isJobApproved = Fns::isRight( $result );
                    $jobId         = $result->coalesce( Fns::identity(), Fns::identity() )->getOrElse( 0 );

                    return [
						'jobId'  => $jobId,
						'status' => $isJobApproved,
                    ];
                }
            );
	}
}
