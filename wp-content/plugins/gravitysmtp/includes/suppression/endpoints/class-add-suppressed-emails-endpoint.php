<?php

namespace Gravity_Forms\Gravity_SMTP\Suppression\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Add_Suppressed_Emails_Endpoint extends Endpoint {

	const ACTION_NAME = 'add_suppressed_emails';

	const PARAM_EMAILS = 'emails';
	const PARAM_NOTE   = 'note';


	/**
	 * @var Suppressed_Emails_Model
	 */
	protected $suppressed_emails;

	protected $required_params = array(
		self::PARAM_EMAILS,
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

		$emails = FILTER_INPUT( INPUT_POST, self::PARAM_EMAILS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$note   = FILTER_INPUT( INPUT_POST, self::PARAM_NOTE, FILTER_DEFAULT );

		// Sanitize
		$note   = ! empty( $note ) ? htmlspecialchars( $note ) : '';
		$valid_emails = array();

		foreach( $emails as $email ) {
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				continue;
			}

			$valid_emails[] = $email;
		}

		foreach( $valid_emails as $email ) {
			$this->suppressed_emails->suppress_email( $email, 'manually_added', $note );
		}

		wp_send_json_success( $emails );
	}

}
