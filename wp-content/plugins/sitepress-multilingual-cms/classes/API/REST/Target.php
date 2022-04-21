<?php

namespace WPML\Rest;

interface ITarget {

	function get_routes();
	function get_allowed_capabilities( \WP_REST_Request $request );
	function get_namespace();

}
