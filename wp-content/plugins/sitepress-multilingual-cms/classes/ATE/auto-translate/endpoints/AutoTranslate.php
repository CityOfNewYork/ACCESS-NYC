<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Left;
use WPML\FP\Obj;
use WPML\FP\Right;
use WPML\TM\API\Jobs;

class AutoTranslate implements IHandler {

	public function run( Collection $data ) {
		global $wpml_translation_job_factory;

		$trid          = $data->get( 'trid' );
		$language_code = $data->get( 'language' );

		if ( $trid && $language_code ) {

			$post_id = \SitePress::get_original_element_id_by_trid( $trid );
			if ( ! $post_id ) {
				return Either::left( 'Post cannot be found by trid' );
			}

			$job_id = $wpml_translation_job_factory->create_local_post_job( $post_id, $language_code );
			$job    = Jobs::get( (int) $job_id );
			if ( ! $job ) {
				return Either::left( 'Job could not be created' );
			}

			if ( Obj::prop( 'automatic', $job ) ) {
				return Right::of( [ 'jobId' => $job_id, 'automatic' => 1 ] );
			} else {
				return Right::of( [ 'jobId' => $job_id, 'automatic' => 0, 'editUrl' => Jobs::getEditUrl( $data->get( 'currentUrl' ), $job_id ) ] );
			}

		}

		return Left::of( 'invalid data' );
	}

}
