<?php

namespace WPML\ST\MO\Generate\MultiSite;

class Condition {
	/**
	 * @return bool
	 */
	public function shouldRunWithAllSites() {
		return is_multisite() && (
				$this->hasPostBodyParam()
				|| is_super_admin()
				|| defined( 'WP_CLI' )
			);
	}

	private function hasPostBodyParam() {
		$request_body = file_get_contents( 'php://input' );
		$data         = filter_var_array( (array)json_decode( $request_body ), FILTER_SANITIZE_STRING );

		return isset( $data['runForAllSites'] ) && $data['runForAllSites'];
	}
}