<?php

namespace WPML\TM\TranslationDashboard\Endpoints;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;

/**
 * It calls `new_duplicated_terms_filter` function which displays the admin notice informing about term taxonomies which have to be synced.
 */
class DisplayNeedSyncMessage {

	public function run( Collection $data ) {
		$postIds = $data->get( 'postIds', [] );
		do_action( 'wpml_new_duplicated_terms', $postIds );

		return Either::of( $postIds );
	}
}
