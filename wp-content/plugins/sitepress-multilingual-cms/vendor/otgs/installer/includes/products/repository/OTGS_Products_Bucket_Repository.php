<?php

class OTGS_Products_Bucket_Repository {
	/**
	 * @param string $api_url
	 * @param string $site_key
	 * @param string $site_url
	 *
	 * @return string|null
	 */
	public function get_products_bucket_url( $api_url, $site_key, $site_url ) {
		$args['body'] = [
			'action'   => 'product_bucket_url',
			'site_key' => $site_key,
			'site_url' => $site_url
		];

		$response = wp_remote_post( $api_url, $args );

		$response_data = $this->get_response_data( $response );
		if ( isset( $response_data->success ) && $response_data->success === true ) {
			return $response_data->bucket->url;
		}

		return null;
	}

	/**
	 * @param array|WP_Error $response
	 *
	 * @return stdClass|null
	 */
	private function get_response_data( $response ) {
		if (
			$response &&
			! is_wp_error( $response ) &&
			isset( $response['response']['code'] ) &&
			$response['response']['code'] == 200
		) {
			$body = wp_remote_retrieve_body( $response );
			if ( $body ) {
				return json_decode( $body );
			}
		}

		return null;
	}
}