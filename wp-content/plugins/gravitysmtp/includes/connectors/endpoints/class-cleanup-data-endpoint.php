<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;

class Cleanup_Data_Endpoint extends Endpoint {

	const PARAM_TARGET = 'target';

	const ACTION_NAME = 'cleanup_data';

	/**
	 * @var Plugin_Opts_Data_Store;
	 */
	protected $data_store;

	public function __construct( $data_store ) {
		$this->data_store = $data_store;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	private function reset_setup_wizard_data() {
		$this->data_store->save( Save_Plugin_Settings_Endpoint::PARAM_SETUP_WIZARD_SHOULD_DISPLAY, 'true' );
		$this->data_store->save( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );
		update_option( 'gravitysmtp_generic', '' );
		update_option( 'gravitysmtp_mailgun', '' );
		update_option( 'gravitysmtp_postmark', '' );
		update_option( 'gravitysmtp_sendgrid', '' );

		wp_send_json_success( __( 'Setup wizard data reset.', 'gravitysmtp' ) );
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$target = filter_input( INPUT_POST, self::PARAM_TARGET );
		$target = htmlspecialchars( $target );

		// handle data clean up or resets here

		switch( $target ) {
			case 'setup_wizard':
				$this->reset_setup_wizard_data();
				break;
			default:
				break;
		}
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( empty( $_REQUEST[ self::PARAM_TARGET ] ) ) {
			return false;
		}

		return true;
	}
}
