<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Connectors;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;

class Slack_Alert_Connector implements Alert_Connector {

	/**
	 * @var Debug_Logger
	 */
	private $debug_logger;

	public function __construct( Debug_Logger $debug_logger ) {
		$this->debug_logger = $debug_logger;
	}

	public function send( $send_args ) {
		$webhook_url = $send_args['webhook_url'];
		$message     = $send_args['message'];

		$request_body = array(
			'text' => $message,
		);

		$request_body = apply_filters( 'gravitysmtp_slack_alert_request_body', $request_body, $send_args );

		$this->debug_logger->log_debug( __METHOD__ . '(): About to make request to a webhook url with the following request args: ' . json_encode( $request_body ) );

		$request_params = array(
			'body'    => json_encode( $request_body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$request = $this->make_request( $webhook_url, $request_params );

		if ( is_wp_error( $request ) ) {
			$this->debug_logger->log_error( __METHOD__ . '(): Request to Webhook URL failed. Details: ' . $request->get_error_message() );

			return false;
		}

		$this->debug_logger->log_debug( __METHOD__ . '(): Request to Webhook URL succeeded.' );

		return true;
	}

	public function make_request( $url, $request_args ) {
		$request = wp_remote_post( $url, $request_args );
		$code    = wp_remote_retrieve_response_code( $request );

		if ( (int) $code !== 200 ) {
			$this->debug_logger->log_error( wp_remote_retrieve_body( $request ) );

			return new \WP_Error( 'Could not send message via Slack.' );
		}

		return true;
	}
}
