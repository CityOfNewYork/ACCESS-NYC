<?php

namespace WPML\TM\Jobs\Query;

use \WPML_TM_Jobs_Search_Params;

class LimitQueryHelper {
	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return string
	 */
	public function get_limit( WPML_TM_Jobs_Search_Params $params ) {
		$result = '';

		if ( $params->get_limit() ) {
			if ( $params->get_offset() ) {
				$result = sprintf( 'LIMIT %d, %d', $params->get_offset(), $params->get_limit() );
			} else {
				$result = sprintf( 'LIMIT %d', $params->get_limit() );
			}
		}

		return $result;
	}
}
