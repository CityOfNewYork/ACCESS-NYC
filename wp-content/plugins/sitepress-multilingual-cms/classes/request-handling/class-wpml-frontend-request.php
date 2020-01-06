<?php

/**
 * Class WPML_Frontend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Frontend_Request extends WPML_Request {
	const COOKIE_NAME = 'wp-wpml_current_language';
	public function get_requested_lang() {
		return $this->wp_api->is_comments_post_page() ? $this->get_comment_language() : $this->get_request_uri_lang();
	}

	protected function get_cookie_name() {

		return self::COOKIE_NAME;
	}
}
