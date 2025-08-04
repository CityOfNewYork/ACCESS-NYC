<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Get_Email_Message_Endpoint extends Endpoint {

	const PARAM_EVENT_ID = 'event_id';

	const ACTION_NAME = 'get_email_message';

	/**
	 * @var Event_Model
	 */
	protected $emails;

	public function __construct( Event_Model $emails ) {
		$this->emails = $emails;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$event_id = filter_input( INPUT_GET, self::PARAM_EVENT_ID, FILTER_SANITIZE_NUMBER_INT );
		$email    = $this->emails->find( array( array( 'id', '=', $event_id ) ) );

		if ( empty( $email[0] ) ) {
			header( 'Content-Type: text/html' );
			/* translators: %d: email ID */
			echo sprintf( __( 'Could not get content for email ID: %d.', 'gravitysmtp' ), $email );
			wp_die();
		}

		header( 'Content-Type: text/html' );
		echo $this->format_email_content( $email[0]['message'] );
		wp_die();
	}

	protected function format_email_content( $content ) {
		if ( $content !== strip_tags( $content ) ) {
			// Remove tracking pixel.
			$content = preg_replace( '/<img src=["\'][^"\']+tracking\/open[^"\']+["\'][\s]*\/>/', '', $content );
			return $content;
		} else {
			return '<pre style="white-space: pre-wrap; word-break: break-all; color: #242748; padding: 20px 25px; font-size: 13px; font-family: inter, -apple-system, blinkmacsystemfont, \'Segoe UI\', roboto, oxygen-sans, ubuntu, cantarell, \'Helvetica Neue\';">' . htmlspecialchars( $content ) . '</pre><style>body { margin: 0; background: #fff; }</style>';
		}
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( empty( $_REQUEST[ self::PARAM_EVENT_ID ] ) ) {
			return false;
		}

		return true;
	}

}
