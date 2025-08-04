<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Email_Log_Single_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Get_Single_Email_Data_Endpoint extends Endpoint {

	const PARAM_EVENT_ID = 'event_id';

	const ACTION_NAME = 'get_single_email';

	/**
	 * @var Log_Details_Model
	 */
	protected $logs;

	/**
	 * @var Event_Model
	 */
	protected $emails;

	protected $required_params = array(
		self::PARAM_EVENT_ID,
	);

	public function __construct( $logs, $email ) {
		$this->logs   = $logs;
		$this->emails = $email;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$email = filter_input( INPUT_POST, self::PARAM_EVENT_ID, FILTER_SANITIZE_NUMBER_INT );

		$data = $this->data( $email );

		if ( ! empty( $data ) ) {
			wp_send_json_success( $data );
		}

		Debug_Logger::log_message(
			sprintf(
				/* translators: %d: event ID */
				__( 'Error retrieving data for event ID: %d.', 'gravitysmtp' ),
				$email
			),
			'error'
		);

		wp_send_json_error(
			/* translators: %d: email ID */
			sprintf( __( 'Could not send get data for event ID: %d.', 'gravitysmtp' ), $email ),
			500
		);
	}

	private function get_i18n() {
		return array(
			'error_alert_title'           => esc_html__( 'Error Saving', 'gravitysmtp' ),
			'error_alert_generic_message' => esc_html__( 'Could not save; please check your logs.', 'gravitysmtp' ),
			'error_alert_close_text'      => esc_html__( 'Close', 'gravitysmtp' ),
			'log_detail'                  => array(
				'top_heading'                    => esc_html__( 'View Email', 'gravitysmtp' ),
				'top_content'                    => esc_html__( 'Detailed log information for this email.', 'gravitysmtp' ),
				'action_button_view_email_label' => esc_html__( 'View Email', 'gravitysmtp' ),
				'action_button_resend_label'     => esc_html__( 'Resend', 'gravitysmtp' ),
				'action_button_print_label'      => esc_html__( 'Print', 'gravitysmtp' ),
				'action_button_export_label'     => esc_html__( 'Export', 'gravitysmtp' ),
				'action_button_delete_label'     => esc_html__( 'Delete Log Entry', 'gravitysmtp' ),
				'main_box_heading'               => esc_html__( 'Email Log', 'gravitysmtp' ),
				'main_box_created_label'         => esc_html__( 'Created', 'gravitysmtp' ),
				'main_box_from_label'            => esc_html__( 'From', 'gravitysmtp' ),
				'main_box_to_label'              => esc_html__( 'To', 'gravitysmtp' ),
				'main_box_subject_label'         => esc_html__( 'Subject', 'gravitysmtp' ),
				'secondary_box_heading'          => esc_html__( 'Technical Information', 'gravitysmtp' ),
				'sidebar_heading'                => esc_html__( 'Status', 'gravitysmtp' ),
				'sidebar_status_label'           => esc_html__( 'Status', 'gravitysmtp' ),
				'sidebar_date_service_label'     => esc_html__( 'Service:', 'gravitysmtp' ),
				'sidebar_has_attachment_label'   => esc_html__( 'Has attachment:', 'gravitysmtp' ),
				'sidebar_log_is_label'           => esc_html__( 'Log ID:', 'gravitysmtp' ),
				'sidebar_source_label'           => esc_html__( 'Source:', 'gravitysmtp' ),
				'sidebar_attachments_heading'    => esc_html__( 'Attachments', 'gravitysmtp' ),
			),
		);
	}

	public function get_log_details( $id ) {
		return $this->logs->full_details( $id );
	}

	public function data( $email_id ) {
		$details = $this->get_log_details( $email_id );

		if ( empty( $details ) ) {
			return array();
		}

		return array(
			'log_detail' => $details,
			'i18n'       => $this->get_i18n(),
			'endpoints'  => array(),
		);
	}

}
