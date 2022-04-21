<?php


namespace WPML\ST\Main\Ajax;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class FetchCompletedStrings implements IHandler {

	public function run( Collection $data ) {
		global $wpdb;

		$strings    = $data->get( 'strings', [] );
		if( count( $strings ) ) {
			$strings_in = wpml_prepare_in( $strings, '%d' );

			$result = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT string_id, language AS lang, value AS translation FROM {$wpdb->prefix}icl_string_translations WHERE string_id IN({$strings_in}) AND status=%d",
					ICL_TM_COMPLETE
				)
			);

			return Either::of( $result );
		} else {
			return Either::of( [] );
		}
	}
}
