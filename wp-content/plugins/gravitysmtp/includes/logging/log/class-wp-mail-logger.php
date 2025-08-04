<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Log;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Header_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;

class WP_Mail_Logger {

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var Event_Model
	 */
	protected $events;

	/**
	 * @var Source_Parser
	 */
	protected $source_parser;

	/**
	 * @var Header_Parser
	 */
	protected $header_parser;

	public function __construct( $logger, $events, $source_parser, $header_parser ) {
		$this->logger        = $logger;
		$this->events        = $events;
		$this->source_parser = $source_parser;
		$this->header_parser = $header_parser;
	}

	public function create_log( $mail_info ) {
		$source      = $this->source_parser->get_source_from_trace( debug_backtrace() );
		$test_mode   = isset( $mail_info['test_mode'] ) ? $mail_info['test_mode'] : false;

		if ( empty( $mail_info['headers'] ) ) {
			$mail_info['headers'] = array();
		} elseif ( ! is_array( $mail_info['headers'] ) ) {
			$mail_info['headers'] = explode( "\n", str_replace( "\r\n", "\n", $mail_info['headers'] ) );
		}

		$from_header = isset( $mail_info['headers']['From'] ) ? $mail_info['headers']['From'] : '';

		if ( empty( $from_header ) ) {
			$from_header = isset( $mail_info['headers']['from'] ) ? $mail_info['headers']['from'] : '';
		}

		$from = '';

		if ( ! empty( $from_header ) ) {
			$froms       = $this->header_parser->get_email_from_header( 'From', $from_header );
			$from        = $froms->first()->mailbox();
		}

		$email_id    = $this->events->create(
			'wp_mail',
			'sent',
			$mail_info['to'],
			$from,
			$mail_info['subject'],
			$mail_info['message'],
			array(
				'headers'     => $mail_info['headers'],
				'attachments' => $mail_info['attachments'],
				'source'      => $source,
			)
		);

		if ( $test_mode ) {
			$this->log( $email_id, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );
			$this->events->update( array( 'status' => 'sandboxed' ), $email_id );
		} else {
			$this->log( $email_id, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );
		}
	}

	public function handle_failed( $wp_error ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$error_data    = $wp_error->get_error_data( 'wp_mail_failed' );
		$error_message = $wp_error->get_error_message( 'wp_mail_failed' );

		if ( ! isset( $error_data['subject'] ) ) {
			return;
		}

		$params = array(
			array( 'service', '=', 'wp_mail' ),
			array( 'subject', '=', $error_data['subject'] ),
			array( 'status', '=', 'sent' ),
		);
		$events = $this->events->find( $params );

		if ( empty( $events[0] ) ) {
			return;
		}

		$email_id = $events[0]['id'];
		$this->events->update( array( 'status' => 'failed' ), $email_id );
		$this->log( $email_id, 'failed', $error_message );
	}

	public function log( $email_id, $action_name, $log_value ) {
		$this->logger->log( $email_id, $action_name, $log_value );
	}

}
