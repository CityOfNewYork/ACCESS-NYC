<?php

namespace Gravity_Forms\Gravity_SMTP\Suppression\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Reactivate_Suppressed_Emails_Endpoint extends Endpoint {

	const ACTION_NAME = 'reactivate_suppressed_emails';

	const PARAM_EMAILS     = 'emails';
	const PARAM_ALL_EMAILS = 'all_emails';

	/**
	 * @var Suppressed_Emails_Model
	 */
	protected $suppressed_emails;

	protected $required_params = array(
		self::PARAM_EMAILS,
		self::PARAM_ALL_EMAILS,
	);

	public function __construct( $suppressed_emails_model ) {
		$this->suppressed_emails = $suppressed_emails_model;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( 'Missing required parameters.', 400 );
		}

		$delete_all_emails = filter_input( INPUT_POST, self::PARAM_ALL_EMAILS );
		$delete_all_emails = htmlspecialchars( $delete_all_emails );

		if ( $delete_all_emails == '1' ) {
			$this->suppressed_emails->delete_all();
			wp_send_json_success( array( 'message' => 'All emails reactivated successfully' ), 200 );
		}

		$emails = filter_input( INPUT_POST, self::PARAM_EMAILS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Split into lines
		$valid_emails = array();

		foreach( $emails as $email ) {
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			$valid_emails[] = $email;
		}

		foreach( $valid_emails as $email ) {
			$this->suppressed_emails->reactivate_email( $email );
		}

		wp_send_json_success( $emails );
	}

}
