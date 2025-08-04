<?php

namespace Gravity_Forms\Gravity_SMTP\Handler\Endpoints;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Attachments_Saver;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Resend_Email_Endpoint extends Endpoint {

	const ACTION_NAME = 'gravitysmtp_resend_email';

	const PARAM_EMAIL_ID = 'email_id';

	/**
	 * @var Event_Model
	 */
	protected $events;

	/**
	 * @var Debug_Logger
	 */
	protected $logger;

	/**
	 * @var Attachments_Saver
	 */
	protected $attachments_handler;

	protected $required_params = array(
		self::PARAM_EMAIL_ID,
	);

	public function __construct( $event_model, $logger, $attachments_handler ) {
		$this->events = $event_model;
		$this->logger = $logger;
		$this->attachments_handler = $attachments_handler;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$email_id = filter_input( INPUT_POST, self::PARAM_EMAIL_ID, FILTER_SANITIZE_NUMBER_INT );
		$email    = $this->events->get( $email_id );
		$extra    = unserialize( $email['extra'] );
		$headers = $extra['headers'];

		// Sanity check to ensure we don't try resending an un-sendable email.
		if ( ! $email['can_resend'] ) {
			// @translators: %s represents the email ID as a numeric string.
			$error_message = __( 'Attempted to resend email %s, but could not due to either the message body not being stored, or attachments not being saved.', 'gravitysmtp' );
			$this->logger->log_warning( sprintf( $error_message, $email_id ) );
			wp_send_json_error( __( 'Email could not be resent as it was not stored with all required values.', 'gravitysmtp' ) );
		}

		if ( ! empty( $extra['attachments'] ) ) {
			foreach( $extra['attachments'] as $key => $og_path ) {
				$new_path = $this->attachments_handler->get_saved_attachment( $email_id, $og_path );
				$extra['attachments'][ $key ] = $new_path;
			}
		}

		$headers['source'] = $extra['source'];

		foreach ( $headers as $idx => $header ) {
			if ( is_a( $header, Recipient_Collection::class ) ) {
				$emails       = $header->recipients();
				$emails_array = array();

				foreach ( $emails as $email_recipient ) {
					$emails_array[] = $email_recipient->email();
				}

				$headers[ $idx ] = implode( ', ', $emails_array );
			}
		}

		$success = wp_mail( $extra['to'], $email['subject'], $email['message'], $headers, $extra['attachments'] );

		if ( ! $success ) {
			wp_send_json_error( __( 'Email could not be resent; check your logs for more details.', 'gravitysmtp' ) );
		}

		wp_send_json_success( $success );
	}

}
