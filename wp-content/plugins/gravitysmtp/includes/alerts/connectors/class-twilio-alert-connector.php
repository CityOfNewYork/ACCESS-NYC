<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Connectors;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;

class Twilio_Alert_Connector implements Alert_Connector {

	const BASE_URL = 'https://api.twilio.com/2010-04-01/Accounts';

	/**
	 * @var Debug_Logger
	 */
	private $debug_logger;

	public function __construct( Debug_Logger $debug_logger ) {
		$this->debug_logger = $debug_logger;
	}

	public function send( $send_args ) {
		$account_id = $send_args['account_id'];
		$auth_token = $send_args['auth_token'];
		$to         = $send_args['to'];
		$from       = $send_args['from'];
		$message    = $send_args['message'];

		$request_body = array(
			'ApplicationSid' => $account_id,
			'Body'           => $message,
			'To'             => $to,
			'From'           => $from,
		);

		$request_body = apply_filters( 'gravitysmtp_twilio_alert_request_body', $request_body, $send_args );

		$request_headers = array(
			'Authorization' => $this->get_auth_header( $account_id, $auth_token ),
		);

		$this->debug_logger->log_debug( __METHOD__ . '(): About to make request to Twilio with the following request args: ' . json_encode( $request_body ) );

		$request_params = array(
			'body'    => $request_body,
			'headers' => $request_headers,
		);

		$request = $this->make_request( $this->get_request_url( $account_id ), $request_params );

		if ( is_wp_error( $request ) ) {
			$this->debug_logger->log_error( __METHOD__ . '(): Request to Twilio failed. Details: ' . $request->get_error_message() );

			return false;
		}

		$this->debug_logger->log_debug( __METHOD__ . '(): Request to Twilio succeeded.' );

		return true;
	}

	public function make_request( $url, $request_args ) {
		$request = wp_remote_post( $url, $request_args );
		$code    = wp_remote_retrieve_response_code( $request );

		if ( (int) $code !== 201 ) {
			$this->debug_logger->log_error( wp_remote_retrieve_body( $request ) );

			return new \WP_Error( 'Could not send message via Twilio.' );
		}

		return true;
	}

	private function get_request_url( $account_id ) {
		return sprintf( '%s/%s/Messages.json', self::BASE_URL, $account_id );
	}

	private function get_auth_header( $account_id, $auth_token ) {
		return 'Basic ' . base64_encode( sprintf( '%s:%s', $account_id, $auth_token ) );
	}
}