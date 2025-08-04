<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;

class Connector_Postmark extends Connector_Base {

	const SETTING_SERVER_API_TOKEN = 'server_api_token';

	protected $name        = 'postmark';
	protected $title       = 'Postmark';
	protected $description = '';
	protected $logo        = 'Postmark';
	protected $full_logo   = 'PostmarkFull';
	protected $url         = 'https://api.postmarkapp.com/email';

	public function get_description() {
		return esc_html__( 'Owned by ActiveCampaign, Postmark is a popular email-sending service with an impressive reputation for reliability and deliverability. Postmark offers a free plan that allows you to send up to 100 emails a month. Over 100, prices vary depending on the number of emails sent. For more information on how to get started with Postmark, read our documentation.', 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_SERVER_API_TOKEN,
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

			$this->logger->log( $email, 'started', __( 'Starting email send for Postmark connector.', 'gravitysmtp' ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

				return true;
			}

			$response = wp_safe_remote_post( $this->url, $params );

			if ( (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
				$this->events->update( array( 'status' => 'failed' ), $email );

				$this->logger->log( $email, 'failed', wp_remote_retrieve_body( $response ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );

			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->events->update( array( 'status' => 'failed' ), $email );

			$this->logger->log( $email, 'failed', $e->getMessage() );

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
		$api_key = $this->get_setting( self::SETTING_SERVER_API_TOKEN );

		if ( ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false ) {
			$html_body = $atts['message'];
		} else {
			$html_body = null;
		}

		$cc  = isset( $atts['headers']['cc'] ) ? $atts['headers']['cc']->as_string( false ) : '';
		$bcc = isset( $atts['headers']['bcc'] ) ? $atts['headers']['bcc']->as_string( false ) : '';

		// Strip tags from plaintext
		$text_body = wp_strip_all_tags( $atts['message'] );

		// Remove leftover double-linebreaks from plaintext.
		$text_body = preg_replace( "/([\r\n]{2,}|[\n]{2,}|[\r]{2,}|[\r\t]{2,}|[\n\t]{2,})/", "\n", $text_body );

		$body = array(
			'from'     => $atts['from'],
			'to'       => $atts['to']->as_string( false ),
			'subject'  => $atts['subject'],
			'textBody' => $text_body,
			'htmlBody' => $html_body,
			'Cc'       => $cc,
			'Bcc'      => $bcc,
		);

		if ( ! empty( $atts['reply_to'] ) ) {
			$body['ReplyTo'] = $atts['reply_to'];
		}

		if ( ! empty( $atts['attachments'] ) ) {
			$body['Attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			$body['Headers'] = array();
			foreach ( $additional_headers as $key => $value ) {
				$body['Headers'][] = array(
					'Name'  => $key,
					'Value' => $value,
				);
			}
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
			'from'        => $this->get_from(),
			'reply_to'    => $this->get_reply_to(),
		);
	}

	protected function get_request_headers( $api_key ) {
		return array(
			'Content-Type'            => 'application/json',
			'Accept'                  => 'application/json',
			'X-Postmark-Server-Token' => $api_key,
		);
	}

	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			$file = false;

			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$fileName  = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$contentId = wp_hash( $attachment );
					$file      = file_get_contents( $attachment );
					$content   = base64_encode( file_get_contents( $attachment ) );
					$mimeType  = mime_content_type( $attachment );
				}
			} catch ( \Exception $e ) {
				$file = false;
			}

			if ( $file === false ) {
				continue;
			}

			$data[] = array(
				'ContentType' => $mimeType,
				'Name'        => $fileName,
				'ContentId'   => $contentId,
				'Content'     => $content,
			);
		}

		return $data;
	}

	public function connector_data() {
		return array(
			self::SETTING_SERVER_API_TOKEN => $this->get_setting( self::SETTING_SERVER_API_TOKEN, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
		);
	}

	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'Postmark Settings', 'gravitysmtp' ),
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
//					array(
//						'component' => 'Toggle',
//						'props'     => array(
//							'initialChecked'  => (bool) $this->get_plugin_setting( 'primary' ) === $this->name,
//							'labelAttributes' => array(
//								'label' => esc_html__( 'If enabled, Postmark will be the default SMTP mailer.', 'gravitysmtp' ),
//							),
//							'labelPosition'   => 'left',
//							'name'            => 'default-mailer',
//							'size'            => 'size-m',
//							'spacing'         => 5,
//							'width'           => 'full',
//						),
//					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Server API Token', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'helpTextAttributes' => array(
								'asHtml'  => true,
								/* translators: 1: opening anchor tag, 2: closing anchor tag */
								'content' => sprintf( __( 'To get a Server API Token, go to the %1$sAPI Token%2$s tab on your Postmark account page.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://account.postmarkapp.com/api_tokens" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_SERVER_API_TOKEN,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_SERVER_API_TOKEN, '' ),
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

	public function migration_map() {
		return array(
			array(
				'original_key' => 'gravityformsaddon_gravityformspostmark_settings',
				'sub_key'      => 'serverToken',
				'new_key'      => self::SETTING_SERVER_API_TOKEN,
			),
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
		$api_key = $this->get_setting( self::SETTING_SERVER_API_TOKEN );
		$url     = str_replace( 'email', 'server', $this->url );

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

		self::$configured = true;

		return true;
	}

}
