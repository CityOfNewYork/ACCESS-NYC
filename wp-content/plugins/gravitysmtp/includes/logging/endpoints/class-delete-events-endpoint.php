<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Delete_Events_Endpoint extends Endpoint {

	const PARAM_EVENT_IDS  = 'event_ids';
	const PARAM_ALL_EVENTS = 'all_events';
	const PARAM_MAX_DATE   = 'max_date';

	const ACTION_NAME = 'delete_events';

	/**
	 * @var Event_Model
	 */
	protected $events;

	protected $required_params = array(
		self::PARAM_EVENT_IDS,
		self::PARAM_ALL_EVENTS,
	);

	public function __construct( Event_Model $events ) {
		$this->events = $events;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$delete_all_events = filter_input( INPUT_POST, self::PARAM_ALL_EVENTS );
		$delete_all_events = htmlspecialchars( $delete_all_events );
		$max_date          = filter_input( INPUT_POST, self::PARAM_MAX_DATE );

		if ( ! empty( $max_date ) ) {
			$max_date = htmlspecialchars( $max_date );
		}

		if ( ! empty( $max_date ) && $delete_all_events == '1' ) {
			$this->events->delete_before( $max_date );
			wp_send_json_success( array( 'message' => __( 'Events deleted successfully', 'gravitysmtp' ) ), 200 );
		}

		if ( $delete_all_events == '1' ) {
			$this->events->delete_all();
			wp_send_json_success( array( 'message' => __( 'All events deleted successfully', 'gravitysmtp' ) ), 200 );
		}

		$event_ids = filter_input( INPUT_POST, self::PARAM_EVENT_IDS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		foreach( $event_ids as $id ) {
			$id_to_delete = filter_var( $id, FILTER_SANITIZE_NUMBER_INT );
			$this->events->delete( $id_to_delete );
		}

		wp_send_json_success( array( 'message' => $event_ids ), 200 );
	}

}
