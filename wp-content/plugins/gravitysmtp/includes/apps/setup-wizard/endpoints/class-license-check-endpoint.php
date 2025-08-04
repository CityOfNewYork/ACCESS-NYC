<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Endpoints;

use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;

class License_Check_Endpoint extends Endpoint {

	const PARAM_LICENSE_KEY = 'license_key';

	const ACTION_NAME = 'check_license';

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$license_key = filter_input( INPUT_POST, self::PARAM_LICENSE_KEY );
		$license_key = htmlspecialchars( $license_key );

		$container    = Gravity_SMTP::container();
		$key_is_empty = empty( $license_key );
		$is_valid     = false;

		if ( ! $key_is_empty ) {
			$license_info = $container->get( Updates_Service_Provider::LICENSE_API_CONNECTOR )->check_license( $license_key );
			$is_valid     = License_Statuses::VALID_KEY === $license_info->get_status();
		}

		if ( ! $is_valid ) {
			wp_send_json_error( __( 'Invalid license key.', 'gravitysmtp' ), 400 );
		}

		wp_send_json_success( array( 'license_key' => $license_key ) );
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( empty( $_REQUEST[ self::PARAM_LICENSE_KEY ] ) ) {
			return false;
		}

		return true;
	}
}
