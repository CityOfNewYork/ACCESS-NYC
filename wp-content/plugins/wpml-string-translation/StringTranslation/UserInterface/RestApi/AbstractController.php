<?php

namespace WPML\StringTranslation\UserInterface\RestApi;

abstract class AbstractController extends \WPML\ST\Rest\Base {

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	function get_allowed_capabilities(\WP_REST_Request $request)
	{
		return ['wpml_manage_string_translation','manage_translations'];
	}
}