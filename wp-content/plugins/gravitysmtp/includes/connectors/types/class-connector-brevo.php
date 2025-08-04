<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

/**
 * Connector for Brevo
 *
 * @since 1.0
 */
class Connector_Brevo extends Connector_Base {

	const SETTING_API_KEY = 'api_key';

	protected $name        = 'brevo';
	protected $title       = 'Brevo';
	protected $description = '';
	protected $logo        = 'Brevo';
	protected $full_logo   = 'BrevoFull';
	protected $url         = 'https://api.brevo.com/v3/smtp/email';

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	public function get_description() {
		return esc_html__( 'Confidently send transactional emails with Brevo, formerly Sendinblue. With an impressive free plan, Brevo allows you to send up to 300 transactional emails a day! And for those who need to send more, simply pay for what you send. For more information on how to get started with Brevo, read our documentation.', 'gravitysmtp' );
	}

	/**
	 * Sends email via Brevo.
	 *
	 * @since 1.0
	 *
	 * @return int Returns the email ID.
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for Brevo connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->url, $params );

			$is_success = in_array( (int) wp_remote_retrieve_response_code( $response ), array( 201, 202 ) );
			if ( ! $is_success ) {
				$this->log_failure( $email, wp_remote_retrieve_body( $response ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );
			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->log_failure( $email, $e->getMessage() );

			return $email;
		}
	}

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_request_params() {
		$atts    = $this->get_send_atts();
		$api_key = $this->get_setting( self::SETTING_API_KEY );

		$body = array(
			'sender'  => array(
				'name'  => isset( $atts['from']['name'] ) ? $atts['from']['name'] : '',
				'email' => $atts['from']['email'],
			),
			'to'      => $atts['to']->as_array(),
			'subject' => $atts['subject'],
		);

		$body['sender'] = array_filter( $body['sender'] );

		// Setting content
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;
		if ( $is_html ) {
			$body['htmlContent'] = $atts['message'];
		} else {
			$body['textContent'] = $atts['message'];
		}

		// Setting cc
		if ( ! empty( $atts['headers']['cc'] ) ) {
			$body['cc'] = $atts['headers']['cc']->as_array();
		}

		// Setting bcc
		if ( ! empty( $atts['headers']['bcc'] ) ) {
			$body['bcc'] = $atts['headers']['bcc']->as_array();
		}

		// Setting reply to
		if ( ! empty( $atts['reply_to'] ) ) {
			if ( isset( $atts['reply_to']['email'] ) ) {
				$reply_to = $atts['reply_to'];
			} else {
				$reply_to = $atts['reply_to'][0];
			}

			$body['replyTo'] = array_filter( array(
				'name'  => isset( $reply_to['name'] ) ? $reply_to['name'] : '',
				'email' => $reply_to['email'],
			) );
		}

		// Setting attachments
		if ( ! empty( $atts['attachments'] ) ) {
			$body['attachment'] = $this->get_attachments( $atts['attachments'] );
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			$body['headers'] = $additional_headers;
		}

		return array(
			'body'    => json_encode( $body ),
			'headers' => $this->get_request_headers( $api_key ),
		);
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_send_atts() {
		$headers = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );
		if ( ! empty( $headers['content-type'] ) ) {
			$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
		}

		return array(
			'to'          => $this->get_att( 'to', '' ),
			'subject'     => $this->get_att( 'subject', '' ),
			'message'     => $this->get_att( 'message', '' ),
			'headers'     => $headers,
			'attachments' => $this->get_att( 'attachments', array() ),
			'from'        => $this->get_from( true ),
			'reply_to'    => $this->get_reply_to( true ),
		);
	}

	/**
	 * Gets a list of attachments, and returns them in a format that can be used by the API.
	 *
	 * @param array $attachments The list of attachments.
	 *
	 * @since 1.0
	 *
	 * @return array Returns an array of attachments. Each item with a 'content' and 'name' property.
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$fileName = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content  = base64_encode( file_get_contents( $attachment ) );

					$data[] = array(
						'name'    => $fileName,
						'content' => $content,
					);
				}
			} catch ( \Exception $e ) {
				continue;
			}
		}

		return $data;
	}

	/**
	 * Gets the headers to be used in the API request.
	 *
	 * @since 1.0
	 *
	 * @return array Returns the header array to be passed to Brevo's API.
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'content-type' => 'application/json',
			'accept'       => 'application/json',
			'api-key'      => $api_key,
		);
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.0
	 *
	 * @param string $email         The email that failed.
	 * @param string $error_message The error message.
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	/**
	 * Connector data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function connector_data() {
		return array(
			self::SETTING_API_KEY          => $this->get_setting( self::SETTING_API_KEY, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
		);
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'Brevo Settings', 'gravitysmtp' ),
			'description' => '',
			'fields'      => array_merge(
				array(
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => array( 4, 0, 4, 0 ),
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'API Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To generate an API key from Brevo, log in to your Brevo dashboard and navigate to the API section. %1$sCreate a new API key%2$s and ensure your %3$sAuthorized IPs settings%2$s are configured correctly.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://app.brevo.com/settings/keys/api" target="_blank" rel="noopener noreferrer">', '</a>', '<a class="gform-link gform-typography--size-text-xs" href="https://help.brevo.com/hc/en-us/articles/5740111683858-Authorize-IP-addresses-for-API-calls-to-improve-security" target="_blank" rel="noopener noreferrer">' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_API_KEY,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_API_KEY, '' ),
						),
					),
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'General Settings', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => 4,
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
				),
				$this->get_from_settings_fields(),
			),
		);
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|\WP_Error Returns true if configured, or a WP_Error object if not.
	 */
	public function is_configured() {
		$valid_api = $this->verify_api_key();

		if ( is_wp_error( $valid_api ) ) {
			self::$configured = $valid_api;

			return $valid_api;
		}

		self::$configured = true;

		return true;
	}

	/**
	 * Verify the API key with the API.
	 *
	 * @since 1.0
	 *
	 * @return true|\WP_Error
	 */
	private function verify_api_key() {
		$api_key = $this->get_setting( self::SETTING_API_KEY );
		$url     = 'https://api.brevo.com/v3/smtp/templates/?limit=1';

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'missing_api_key', __( 'No API Key provided.', 'gravitysmtp' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $this->get_request_headers( $api_key ),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) != '200' ) {
			return new \WP_Error( 'invalid_api_key', __( 'Invalid API Key provided.', 'gravitysmtp' ) );
		}

		return true;
	}

}
