<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Delete_Email_Endpoint extends Endpoint {

	const PARAM_EVENT_ID = 'event_id';

	const ACTION_NAME = 'delete_email';

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

		$event_id = filter_input( INPUT_POST, self::PARAM_EVENT_ID, FILTER_SANITIZE_NUMBER_INT );

		$this->emails->delete( $event_id );

		wp_send_json_success( array( 'message' => __( 'Event deleted successfully', 'gravitysmtp' ) ), 200 );
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( empty( $_REQUEST[ self::PARAM_EVENT_ID ] ) ) {
			return false;
		}

		return true;
	}

}
