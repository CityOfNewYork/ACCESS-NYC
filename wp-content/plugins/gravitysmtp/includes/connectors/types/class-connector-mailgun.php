<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

/**
 * Connector for Mailgun
 *
 * @since 1.0
 */
class Connector_Mailgun extends Connector_Base {

	const SETTING_API_KEY         = 'api_key';
	const SETTING_REGION          = 'region';
	const SETTING_DOMAIN          = 'domain';
	const SETTING_USE_RETURN_PATH = 'use_return_path';

	const OPTION_REGION_US = 'us';
	const OPTION_REGION_EU = 'eu';

	const API_URL_US = 'https://api.mailgun.net/v3/';
	const API_URL_EU = 'https://api.eu.mailgun.net/v3/';

	protected $name        = 'mailgun';
	protected $title       = 'Mailgun';
	protected $disabled    = true;
	protected $description = '';
	protected $logo        = 'Mailgun';
	protected $full_logo   = 'MailgunFull';

	public function get_description() {
		return esc_html__( 'Mailgun is a transactional email service that provides industry-leading reliability, compliance, and speed. Offering a 30-day trial, Mailgun’s premium service starts at $35 a month, which allows you to send up to 50,000 emails. For more information on how to get started with Mailgun, read our documentation.', 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	/**
	 * Sending logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$params = $this->get_request_params();
			$email  = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for Mailgun connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->get_api_url(), $params );

			if ( is_wp_error( $response ) ) {
				$this->events->update( array( 'status' => 'failed' ), $email );
				$this->logger->log( $email, 'failed', $response->get_error_message() );

				return $email;
			}

			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( (int) $response_code !== 200 ) {
				$this->events->update( array( 'status' => 'failed' ), $email );
				$this->logger->log( $email, 'failed', $response_body );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );

			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->events->update( array( 'status' => 'failed' ), $email );

			$this->logger->log( $email, 'failed', $e->getMessage() );

			return false;
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

		if ( ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false ) {
			$content_type = 'text/html';
		} else {
			$content_type = 'text/plain';
		}

		$content_type = $this->get_att( 'content_type', $content_type );

		$body = [
			'from'           => $atts['from'],
			'subject'        => $atts['subject'],
			'h:Content-Type' => $content_type
		];

		if ( ! empty( $atts['reply_to'] ) ) {
			$body['h:Reply-To'] = $atts['reply_to'];
		}

		unset( $atts['headers']['reply-to'] );

		if ( $content_type === 'text/html' ) {
			$body['html'] = $atts['message'];
		} else {
			$body['text'] = $atts['message'];
		}

		$cc  = isset( $atts['headers']['cc'] ) ? $atts['headers']['cc']->as_string( true ) : '';
		$bcc = isset( $atts['headers']['bcc'] ) ? $atts['headers']['bcc']->as_string( true ) : '';

		$recipients = array(
			'to'  => $atts['to']->as_string( true ),
			'cc'  => $cc,
			'bcc' => $bcc,
		);

		$body = array_merge( $body, array_filter( $recipients ) );

		foreach ( $this->get_filtered_message_headers() as $key => $value ) {
			$header_key = sprintf( 'h:%s', $key );

			if ( isset( $body[ $header_key ] ) ) {
				continue;
			}

			$body[ $header_key ] = $value;
		}

		if ( (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ) ) {
			$body['sender'] = $atts['from'];
		}

		$params = [
			'body'    => $body,
			'headers' => $this->get_request_headers( $api_key )
		];

		if ( ! empty( $atts['attachments'] ) ) {
			$params = $this->get_attachments( $params, $atts['attachments'] );
		}

		return $params;
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_send_atts() {
		return array(
			'to'          => $this->get_att( 'to', array() ),
			'subject'     => $this->get_att( 'subject', '' ),
			'message'     => $this->get_att( 'message', '' ),
			'headers'     => $this->get_parsed_headers( $this->get_att( 'headers', array() ) ),
			'attachments' => $this->get_att( 'attachments', array() ),
			'from'        => $this->get_from(),
			'reply_to'    => $this->get_reply_to(),
		);
	}

	/**
	 * Get the correct API URL based on region, and include the domain for requests.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function get_api_url( $endpoint = 'messages', $include_domain = true ) {
		$region = $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_US );
		$url    = $region === self::OPTION_REGION_US ? self::API_URL_US : self::API_URL_EU;
		if ( $include_domain ) {
			$url .= $this->get_setting( self::SETTING_DOMAIN, '' ) . '/';
		}

		$url .= $endpoint;

		return sanitize_text_field( $url );
	}

	/**
	 * Get attachments for this email.
	 *
	 * @since 1.0
	 *
	 * @param $params
	 * @param $attachments
	 *
	 * @return array
	 */
	protected function get_attachments( $params, $attachments ) {
		$data    = array();
		$payload = '';

		foreach ( $attachments as $custom_name => $attachment ) {
			$file = false;

			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$fileName  = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$file      = file_get_contents( $attachment );
				}
			} catch ( \Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$data[] = [
				'content' => $file,
				'name'    => $fileName,
			];
		}

		if ( ! empty( $data ) ) {
			$boundary = hash( 'sha256', uniqid( '', true ) );

			foreach ( $params['body'] as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $child_key => $child_value ) {
						$payload .= '--' . $boundary;
						$payload .= "\r\n";
						$payload .= 'Content-Disposition: form-data; name="' . $key . "\"\r\n\r\n";
						$payload .= $child_value;
						$payload .= "\r\n";
					}
				} else {
					$payload .= '--' . $boundary;
					$payload .= "\r\n";
					$payload .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
					$payload .= $value;
					$payload .= "\r\n";
				}
			}

			foreach ( $data as $key => $attachment ) {
				$payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="attachment[' . $key . ']"; filename="' . $attachment['name'] . '"' . "\r\n\r\n";
				$payload .= $attachment['content'];
				$payload .= "\r\n";
			}

			$payload .= '--' . $boundary . '--';

			$params['body'] = $payload;

			$params['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;

			$this->attributes['headers']['content-type'] = 'multipart/form-data';
		}

		return $params;
	}

	/**
	 * Get the common headers for making the API request (with API keys).
	 *
	 * @since 1.0
	 *
	 * @return string[]
	 */
	protected function get_request_headers( $api_key ) {
		return array(
			'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key )
		);
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
			self::SETTING_REGION           => $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_US ),
			self::SETTING_DOMAIN           => $this->get_setting( self::SETTING_DOMAIN, '' ),
			self::SETTING_USE_RETURN_PATH  => (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ),
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
			'title'       => esc_html__( 'Mailgun Settings', 'gravitysmtp' ),
			'description' => '',
			'fields'      => array_merge(
				array(
					array(
						'component' => 'Heading',
						'props'     => array(
							'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
							'size'    => 'text-sm',
							'spacing' => [ 4, 0, 4, 0 ],
							'tagName' => 'h3',
							'type'    => 'boxed',
							'weight'  => 'medium',
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Mailgun API Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To obtain a %1$sMailgun API Key%2$s, please navigate to the \'Mailgun API Keys\' and generate a key.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://app.mailgun.com/settings/api_security" target="_blank" rel="noopener noreferrer">', '</a>' ),
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
					array(
						'component' => 'Toggle',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'If Return Path is enabled this adds the return path to the email header which indicates where non-deliverable notifications should be sent. Bounce messages may be lost if not enabled.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
								'spacing' => [ 2, 0, 0, 0 ],
							),
							'helpTextWidth'      => 'full',
							'initialChecked'     => (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Return Path', 'gravitysmtp' ),
							),
							'labelPosition'      => 'left',
							'name'               => self::SETTING_USE_RETURN_PATH,
							'size'               => 'size-m',
							'spacing'            => 5,
							'width'              => 'full',
						),
					),
					array(
						'component' => 'Box',
						'props'     => array(
							'display' => 'block',
							'spacing' => 6,
						),
						'fields'    => array(
							array(
								'component' => 'Label',
								'props'     => array(
									'label'   => __( 'Region', 'gravitysmtp' ),
									'weight'  => 'medium',
									'size'    => 'text-sm',
									'spacing' => 2,
								),
							),
							array(
								'component' => 'InputGroup',
								'props'     => array(

									'customAttributes' => array(
										'style' => array(
											'display' => 'flex',
										),
									),
									'id'               => self::SETTING_REGION . '_group',
									'initialValue'     => $this->get_setting( self::SETTING_REGION, self::OPTION_REGION_US ),
									'inputType'        => 'radio',
									'spacing'          => 2,
									'data'             => array(
										array(
											'id'              => self::SETTING_REGION . '_' . self::OPTION_REGION_US,
											'name'            => self::SETTING_REGION,
											'value'           => self::OPTION_REGION_US,
											'size'            => 'size-md',
											'spacing'         => array( 0, 4, 0, 0 ),
											'labelAttributes' => array(
												'label'  => __( 'US', 'gravitysmtp' ),
												'size'   => 'text-sm',
												'weight' => 'regular',
											),
										),
										array(
											'id'              => self::SETTING_REGION . '_' . self::OPTION_REGION_EU,
											'name'            => self::SETTING_REGION,
											'value'           => self::OPTION_REGION_EU,
											'size'            => 'size-md',
											'spacing'         => array( 0, 4, 0, 0 ),
											'labelAttributes' => array(
												'label'  => __( 'EU', 'gravitysmtp' ),
												'size'   => 'text-sm',
												'weight' => 'regular',
											),
										),
									),
								),
							),
							array(
								'component' => 'Text',
								'props'     => array(
									'asHtml'  => true,
									/* translators: 1: opening anchor tag, 2: closing anchor tag */
									'content' => sprintf( __( 'Choose your message sending endpoint. If subject to EU regulations, consider the EU region. For more information, visit %1$sMailgun.com%2$s.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://www.mailgun.com/regions" target="_blank" rel="noopener noreferrer">', '</a>' ),
									'size'    => 'text-xs',
									'weight'  => 'regular',
								),
							),
						),
					),
				),
				$this->get_from_settings_fields(),
				array(
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Sending Domain', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'Verify your Mailgun domain name. %1$sView domains%2$s.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://login.mailgun.com/login" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_DOMAIN,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_DOMAIN, '' ),
						),
					),
				),
			),
		);
	}

	public function migration_map() {
		return array(
			array(
				'original_key' => 'gravityformsaddon_gravityformsmailgun_settings',
				'sub_key'      => 'apiKey',
				'new_key'      => self::SETTING_API_KEY,
			),
			array(
				'original_key' => 'gravityformsaddon_gravityformsmailgun_settings',
				'sub_key'      => 'region',
				'new_key'      => self::SETTING_REGION,
			)
		);
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
		$url     = $this->get_api_url( 'messages', true );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'missing_api_key', __( 'No API Key provided.', 'gravitysmtp' ) );
		}

		$data = array(
			'headers' => $this->get_request_headers( $api_key ),
			'body'    => array(
				"from"    => "string",
				"to"      => "string",
				"subject" => "string",
				"html"    => "string",
			),
		);

		$request = wp_remote_post( $url, $data );

		$code = wp_remote_retrieve_response_code( $request );

		if ( (int) $code === 401 ) {
			return new \WP_Error( 'invalid_api_key', __( 'Invalid API Key provided.', 'gravitysmtp' ) );
		}

		return true;
	}

	/**
	 * Verify the sending domain with the API.
	 *
	 * @since 1.0
	 *
	 * @return true|\WP_Error
	 */
	private function verify_domain() {
		$api_key = $this->get_setting( self::SETTING_API_KEY );
		$url     = $this->get_api_url( 'domains/' . $this->get_setting( self::SETTING_DOMAIN ), false );

		$data = array(
			'headers' => $this->get_request_headers( $api_key ),
		);

		$result = wp_remote_get( $url, $data );

		if ( wp_remote_retrieve_response_code( $result ) == '404' ) {
			return new \WP_Error( 'invalid_domain', __( 'Invalid sending domain provided.', 'gravitysmtp' ) );
		}

		return true;
	}

	/**
	 * Determine if the API credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|\WP_Error
	 */
	public function is_configured() {
		$valid_api = $this->verify_api_key();

		if ( is_wp_error( $valid_api ) ) {
			self::$configured = $valid_api;

			return $valid_api;
		}

		$valid_domain = $this->verify_domain();

		if ( is_wp_error( $valid_domain ) ) {
			self::$configured = $valid_domain;

			return $valid_domain;
		}

		self::$configured = true;

		return true;
	}

}
