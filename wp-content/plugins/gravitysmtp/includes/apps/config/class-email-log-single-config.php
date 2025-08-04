<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\Providers\Config_Collection_Service_Provider;

class Email_Log_Single_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	public function should_enqueue() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page     = htmlspecialchars( $page );
		$event_id = filter_input( INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT );

		if ( $page !== 'gravitysmtp-activity-log' || empty( $event_id ) ) {
			return false;
		}

		return true;
	}

	public function get_i18n() {
		return array(
			'error_alert_title'           => esc_html__( 'Error Saving', 'gravitysmtp' ),
			'error_alert_generic_message' => esc_html__( 'Could not save; please check your logs.', 'gravitysmtp' ),
			'error_alert_close_text'      => esc_html__( 'Close', 'gravitysmtp' ),
			'log_detail'                  => array(
				'top_heading'                       => esc_html__( 'Email Log Details', 'gravitysmtp' ),
				// 'top_content'                    => esc_html__( '', 'gravitysmtp' ),
										'top_error' => esc_html__( 'The email could not be found, go back and try again.', 'gravitysmtp' ),
				'action_button_view_email_label'    => esc_html__( 'View Email', 'gravitysmtp' ),
				'action_button_resend_label'        => esc_html__( 'Resend', 'gravitysmtp' ),
				'action_button_print_label'         => esc_html__( 'Print', 'gravitysmtp' ),
				'action_button_export_label'        => esc_html__( 'Export', 'gravitysmtp' ),
				'action_button_delete_label'        => esc_html__( 'Delete Log Entry', 'gravitysmtp' ),
				'back_button_label'                 => esc_html__( 'Back to Email Log', 'gravitysmtp' ),
				'main_box_heading'                  => esc_html__( 'Email Details', 'gravitysmtp' ),
				'main_box_bcc_label'                => esc_html__( 'BCC', 'gravitysmtp' ),
				'main_box_cc_label'                 => esc_html__( 'CC', 'gravitysmtp' ),
				'main_box_date_label'               => esc_html__( 'Date Sent', 'gravitysmtp' ),
				'main_box_from_label'               => esc_html__( 'From', 'gravitysmtp' ),
				'main_box_to_label'                 => esc_html__( 'To', 'gravitysmtp' ),
				'main_box_subject_label'            => esc_html__( 'Subject', 'gravitysmtp' ),
				'nav_button_next_title'             => esc_html__( 'Navigate to the next log detail', 'gravitysmtp' ),
				'nav_button_prev_title'             => esc_html__( 'Navigate to the previous log detail', 'gravitysmtp' ),
				'secondary_box_heading'             => esc_html__( 'Technical Information', 'gravitysmtp' ),
				'secondary_box_log_heading'         => esc_html__( 'Log', 'gravitysmtp' ),
				'secondary_box_headers_heading'     => esc_html__( 'Headers', 'gravitysmtp' ),
				'sidebar_status_heading'            => esc_html__( 'Log Details', 'gravitysmtp' ),
				'sidebar_status_label'              => esc_html__( 'Status:', 'gravitysmtp' ),
				'sidebar_opened_label'              => esc_html__( 'Opened:', 'gravitysmtp' ),
				'sidebar_service_label'             => esc_html__( 'Service:', 'gravitysmtp' ),
				'sidebar_has_attachment_label'      => esc_html__( 'Has attachment:', 'gravitysmtp' ),
				'sidebar_log_id_label'              => esc_html__( 'Log ID:', 'gravitysmtp' ),
				'sidebar_source_label'              => esc_html__( 'Source:', 'gravitysmtp' ),
				'sidebar_attachments_heading'       => esc_html__( 'Attachments', 'gravitysmtp' ),
				'view_email_desktop_mode'           => esc_html__( 'Preview email in desktop mode', 'gravitysmtp' ),
				'view_email_mobile_mode'            => esc_html__( 'Preview email in mobile mode', 'gravitysmtp' ),
			),
			/* translators: %1$s is the body of the ajax request. */
			'resending_email'             => esc_html__( 'Resending email: %1$s', 'gravitysmtp' ),
			/* translators: %1$s is the error message. */
			'resending_error'             => esc_html__( 'Error resending email: %1$s', 'gravitysmtp' ),
			'snackbar_resend_error'       => esc_html__( 'Error resending email', 'gravitysmtp' ),
			'snackbar_resend_success'     => esc_html__( 'Email successfully resent', 'gravitysmtp' ),
		);
	}

	public function get_log_single_data() {
		return array(
			'log_detail' => array(
				'default' => $this->get_default_log_details(),
				'value'   => $this->get_log_details(),
			),
		);
	}

	private function get_default_log_data() {
		$headers = array(
			'body'    => array(
				'to'      => 'someuser@its.rochester.edu',
				'subject' => 'My mail message is about.',
			),
			'headers' => array(
				'from: somesender@mail.rochester.edu',
				'content-type: text/html',
			),
		);

		$text = 'Received: from antivirus1.its.rochester.edu (antivirus.its.rochester.edu [128.151.57.50])
				by mail.rochester.edu (8.12.8/8.12.4) with ESMTP id h20GQs90002563;
				Mon, 24 Mar 2003 11:26:54 -0500 (EST)
				Received: from antivirus.its.rochester.edu (localhost [127.0.0.1])
				by antivirus1.its.rochester.edu (8.12.8/8.12.4) with ESMTP id h20GOr0x003450;
				Mon, 24 Mar 2003 11:26:54 -0500 (EST)
				Received: from galileo.cc.rochester.edu (galileo.cc.rochester.edu [128.151.224.6])
				by antivirus1.its.rochester.edu (8.12.8/8.12.4) with SMTP id h20GOrDC003447;
				Mon, 24 Mar 2003 11:26:53 -0500 (EST)
				Received: (from majord@localhost)
				by galileo.cc.rochester.edu (8.12.8/8.12.4) id h20GQg91029757;
				Mon, 24 Mar 2003 11:26:52 -0500 (EST)
				Date: Mon, 24 Mar 2003 11:26:50 -0500 (EST)
				From: somesender@mail.rochester.edu
				Message-Id: <200303241626.h20GQoit002507@mail.rochester.edu>
				To: someuser@its.rochester.edu
				Subject: My mail message is about.';

		$lines                          = explode( "\n", $text );
		$temp_log_detail_techincal_data = array();

		foreach ( $lines as $index => $line ) {
			$temp_log_detail_techincal_data[ $index ] = $line;
		}

		return array(
			'log'     => $temp_log_detail_techincal_data,
			'headers' => $headers,
		);
	}

	private function get_default_log_details() {
		return array(
			'date'                  => 'March 7, 2023 at 8:07 pm',
			'from'                  => 'carl@gmail.com',
			'to'                    => 'carl@hancock.io',
			'subject'               => 'New WordPress User Registration',
			'technical_information' => $this->get_default_log_data(),
			'status'                => array(
				'label'  => 'Sent',
				'status' => 'active',
			),
			'service'               => 'Amazon SES',
			'has_attachment'        => 3,
			'attachments'           => array(
				array(
					'file_name'      => 'test.pdf',
					'file_path'      => 'http://example.com/wp-content/uploads/gravity_forms/2023/08/test.pdf',
					'file_extension' => 'pdf',
				),
				array(
					'file_name'      => 'test2.pdf',
					'file_path'      => 'http://example.com/wp-content/uploads/gravity_forms/2023/08/test2.pdf',
					'file_extension' => 'pdf',
				),
				array(
					'file_name'      => 'test3.pdf',
					'file_path'      => 'http://example.com/wp-content/uploads/gravity_forms/2023/08/test3.pdf',
					'file_extension' => 'pdf',
				),
			),
			'log_id'                => '12',
			'opened'                => 'Yes',
			'clicked'               => 'No',
			'source'                => 'Gravity Forms',
		);
	}

	private function extract_email( $string ) {
		$regex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}/';
		preg_match( $regex, $string, $matches );

		return $matches ? $matches[0] : null;
	}

	private function get_log_details() {
		$id = filter_input( INPUT_GET, 'event_id', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $id ) ) {
			return array();
		}

		$container = Gravity_SMTP::container();
		$logs      = $container->get( Connector_Service_Provider::LOG_DETAILS_MODEL );

		return $logs->full_details( $id );
	}

	private function convert_dates_to_timezone( $date ) {
		$gmt_time   = new \DateTimeZone( 'UTC' );
		$local_time = new \DateTimeZone( wp_timezone_string() );
		$datetime   = new \DateTime( $date, $gmt_time );
		$datetime->setTimezone( $local_time );

		return $datetime->format( 'F d, Y \a\t h:ia' );
	}

	private function get_attachments( $attachments ) {
		if ( ! is_array( $attachments ) ) {
			return array();
		}

		$attachments_arr = array();
		foreach ( $attachments as $attachment ) {
			$attachments_arr[] = array(
				'file_name'      => esc_html( basename( $attachment ) ),
				'file_path'      => esc_html( $attachment ),
				'file_extension' => esc_html( pathinfo( $attachment, PATHINFO_EXTENSION ) ),
			);
		}

		return $attachments_arr;
	}

	public function data() {
		$log_config = Gravity_SMTP::container()->get( App_Service_Provider::EMAIL_LOG_CONFIG );

		return array(
			'components' => array(
				'activity_log' => array(
					'data'      => array_merge( $this->get_log_single_data(), $log_config->get_log_data() ),
					'i18n'      => array_merge( $this->get_i18n(), $log_config->get_i18n() ),
					'endpoints' => array(),
				),
			),
		);
	}
}
