<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;

class GetNumberOfPosts {

	public function run( Collection $data, \wpdb $wpdb ) {
		$postIn = wpml_prepare_in( $data->get( 'postTypes', PostTypes::getAutomaticTranslatable() ) );

		return $wpdb->get_var(
			"SELECT COUNT(id) FROM {$wpdb->posts} WHERE post_type IN ({$postIn}) AND post_status='publish'"
		);
	}
}
