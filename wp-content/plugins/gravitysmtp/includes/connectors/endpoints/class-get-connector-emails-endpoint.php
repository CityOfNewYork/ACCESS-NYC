<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_SMTP\Models\Notifications_Model;

class Get_Connector_Emails extends Endpoint {

	const PARAM_CONNECTOR_NAME = 'connector_name';

	const ACTION_NAME = 'get_connector_emails';

	/**
	 * @var Notifications_Model
	 */
	protected $notifications;


	protected $required_params = array(
		self::PARAM_CONNECTOR_NAME,
	);

	public function __construct( $notifications ) {
		$this->notifications = $notifications;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$connector                   = rgpost( self::PARAM_CONNECTOR_NAME );
		$notifications_for_connector = $this->notifications->by_service( $connector );
		$emails                      = array();

		if ( empty( $notifications_for_connector ) ) {
			wp_send_json_success( $emails );
		}

		$notifications_for_connector = array_map( function ( $row ) use ( $connector ) {
			$notifications = rgar( $row, 'notifications' );
			$notifications = json_decode( $notifications, true );

			$filtered = array_filter( $notifications, function ( $notification ) use ( $connector ) {
				return rgar( $notification, 'service' ) == $connector && is_email( rgar( $notification, 'from' ) );
			} );

			return array_values( wp_list_pluck( $filtered, 'from' ) );
		}, $notifications_for_connector );

		foreach ( $notifications_for_connector as $found_emails ) {
			$emails = array_merge( $emails, $found_emails );
		}

		wp_send_json_success( $emails );
	}

}
