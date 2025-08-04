<?php

namespace Gravity_Forms\Gravity_SMTP\Telemetry;

use Gravity_Forms\Gravity_Tools\Telemetry\Telemetry_Processor;

class Telemetry_Background_Processor extends Telemetry_Processor {

	public function send_data( $entries ) {
		// allow overriding the endpoint to use the local or staging environment for testing purposes.
		$endpoint = defined( 'GF_TELEMETRY_ENDPOINT' ) ? GF_TELEMETRY_ENDPOINT : self::TELEMETRY_ENDPOINT;
		$site_url = get_site_url();

		if ( array_key_exists( 'of', $entries ) ) {
			$entries = array( $entries );
		}

		$data = array(
			'license_key_md5' => md5( get_option( 'rg_gforms_key', '' ) ),
			'site_url'        => $site_url,
			'product'         => 'gravitysmtp',
			'tag'             => 'system_report',
			'data'            => $entries,
		);

		return wp_remote_post(
			$endpoint . 'api/telemetry_data_bulk',
			array(
				'headers'     => array(
					'Content-Type'  => 'application/json',
					'Authorization' => sha1( $site_url ),
				),
				'method'      => 'POST',
				'data_format' => 'body',
				'body'        => json_encode( $data ),
			)
		);
	}

}