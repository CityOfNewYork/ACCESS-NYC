<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;

/**
 * Connector for Mailchimp
 *
 * @since 1.4.2
 */
class Connector_Mailchimp extends Connector_Base {

	const SETTING_API_KEY         = 'api_key';
	const SETTING_USE_RETURN_PATH = 'use_return_path';

	protected $name        = 'mailchimp';
	protected $title       = 'Mailchimp';
	protected $logo        = 'Mailchimp';
	protected $full_logo   = 'MailchimpFull';
	protected $url         = 'https://mandrillapp.com/api/1.0/';

	public function get_description() {
		return esc_html__( 'Reach inboxes when it matters most. Send email notifications to your contacts with MailChimp Transactional Email.', 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_API_KEY,
	);

	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'mailchimp_integration' );

		return $data;
	}

	/**
	 * Sending logic.
	 *
	 * @since 1.4.2
	 *
	 * @return bool
	 */
	public function send() {
		try {
			$atts   = $this->get_send_atts();
			$source = $this->get_att( 'source' );
			$from   = $this->get_from( true );
			$params = $this->get_request_params();
			$email = $this->email;

			$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

			$this->logger->log( $email, 'started', __( 'Starting email send for Mailchimp connector.', 'gravitysmtp' ) );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Starting email send with Mailchimp connector and the following params: ' . json_encode( $params) ) );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, sprintf( 'Using From Name: %s, From Email: %s', $from['name'], $from['email'] ) ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Test mode is enabled, sandboxing email.' ) );

				return true;
			}

			$url      = $this->url . 'messages/send';
			$response = wp_safe_remote_post( $url, $params );
			$body     = wp_remote_retrieve_body( $response );

			$decoded = json_decode( $body, true );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Received response of: ' . $body ) );

			$is_success = (int) wp_remote_retrieve_response_code( $response ) === 200 && $decoded[0]['status'] !== 'rejected';
			if ( ! $is_success ) {
				$this->log_failure( $email, $body );
				$this->debug_logger->log_error( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email failed to send. Details: ' . $body ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );
			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );
			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email successfully sent.' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->log_failure( $email, $e->getMessage() );
			$this->debug_logger->log_error( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email failed to send. Details: ' . $e->getMessage() ) );

			return $email;
		}
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.4.2
	 *
	 * @param string $email         The email that failed.
	 * @param string $error_message The error message.
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	/**
	 * Get the request parameters for sending email through connector.
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	public function get_request_params() {
		$atts    = $this->get_send_atts();
		$api_key = $this->get_setting( self::SETTING_API_KEY );

		$body = array(
			'key'     => $api_key,
			'message' => array(
				'subject'    => $atts['subject'],
				'from_email' => $atts['from']['email'],
				'headers' => array(),
			),
		);

		if ( ! empty( $atts['from']['name'] ) ) {
			$body['message']['from_name'] = $atts['from']['name'];
		}

		if ( ! empty( $atts['to'] ) ) {
			$body['message']['to'] = $atts['to']->as_array();
		}

		// Setting content
		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		if ( $is_html ) {
			$body['message']['html'] = $atts['message'];
			$body['message']['text'] = wp_strip_all_tags( $atts['message'] );
		} else {
			$body['message']['text'] = $atts['message'];
		}

		// Setting reply-to
		if ( ! empty( $atts['headers']['reply-to'] ) ) {
			$address                                = str_replace( 'Reply-To: ', '', $atts['headers']['reply-to'] );
			$body['message']['headers']['reply-to'] = $address;
		}

		// Setting cc
		if ( ! empty( $atts['headers']['cc'] ) ) {
			foreach ( $atts['headers']['cc']->as_array() as $recipient ) {
				if ( isset( $recipient['email'] ) ) {
					$body['message']['to'][] = array(
						'type' => 'cc',
						'email' => $recipient['email'],
					);
				}
			}
		}

		// Setting bcc
		if ( ! empty( $atts['headers']['bcc'] ) ) {
			foreach ( $atts['headers']['bcc']->as_array() as $recipient ) {
				if ( isset( $recipient['email'] ) ) {
					$body['message']['to'][] = array(
						'type' => 'bcc',
						'email' => $recipient['email'],
					);
				}
			}
		}

		if ( (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ) ) {
			$body['message']['return_path_domain'] = $atts['from']['email'];
		}

		// Setting attachments
		if ( ! empty( $atts['attachments'] ) ) {
			$body['message']['attachments'] = $this->get_attachments( $atts['attachments'] );
		}

		return array(
			'body'    => json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);
	}

	/**
	 * Gets a list of attachments, and returns them in a format that can be used by the API.
	 *
	 * @param array $attachments The list of attachments.
	 *
	 * @since 1.4.2
	 *
	 * @return array Returns an array of attachments. Each item with a 'content' and 'name' property.
	 */
	protected function get_attachments( $attachments ) {
		$data = array();

		foreach ( $attachments as $custom_name => $attachment ) {
			try {
				if ( is_file( $attachment ) && is_readable( $attachment ) ) {
					$file_name = is_numeric( $custom_name ) ? basename( $attachment ) : $custom_name;
					$content  = base64_encode( file_get_contents( $attachment ) );

					$data[] = array(
						'name'    => $file_name,
						'content' => $content,
						'type' => mime_content_type( $attachment ),
					);
				}
			} catch ( \Exception $e ) {
				continue;
			}
		}

		return $data;
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.4.2
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
	 * Connector data.
	 *
	 * @since 1.4.2
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
			self::SETTING_USE_RETURN_PATH  => (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ),
		);
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	public function settings_fields() {
		return array(
			'title'       => esc_html__( 'Mailchimp Settings', 'gravitysmtp' ),
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
								'content' => sprintf( __( 'To generate an API key from Mailchimp, navigate to the %ssettings of your Mailchimp Transactional account%s and look for the API Keys section.', 'gravitysmtp' ), '<a class="gform-link gform-typography--size-text-xs" href="https://mandrillapp.com/settings" target="_blank" rel="noopener noreferrer">', '</a>' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'name'               => self::SETTING_API_KEY,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_API_KEY, '' ),
						),
					),
				),
				array(
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
					)
				),
				$this->get_from_settings_fields(),
			),
		);
	}

	public function is_configured() {
		static $configured;

		if ( ! is_null( $configured ) ) {
			return $configured;
		}

		$base_url = $this->url . 'users/ping';
		$body     = json_encode( array(
			'key' => $this->get_setting( self::SETTING_API_KEY, '' ),
		) );

		$params = array(
			'body'    => $body,
			'headers' => array( 'Content-Type' => 'application/json' ),
		);

		$request = wp_remote_post( $base_url, $params );
		$code    = wp_remote_retrieve_response_code( $request );

		if ( $code === 200 ) {
			$configured = true;

			return true;
		}

		$error      = new \WP_Error( 'authentication_error', __( 'Could not authenticate with Mailchimp.', 'gravitysmtp' ) );
		$configured = $error;

		return $error;
	}

	protected function get_authenticated_domains() {
		$base_url = $this->url . 'senders/domains';
		$body     = json_encode( array(
			'key' => $this->get_setting( self::SETTING_API_KEY, '' ),
		) );

		$params = array(
			'body'    => $body,
			'headers' => array( 'Content-Type' => 'application/json' ),
		);

		$request    = wp_remote_post( $base_url, $params );
		$code       = wp_remote_retrieve_response_code( $request );
		$no_results = array(
			__( 'No authenticated Domains found in your account. Sending will not be possible until you add a verified domain to your Mailchimp Account.', 'gravitysmtp' ),
		);

		if ( $code !== 200 ) {
			return $no_results;
		}

		$body    = wp_remote_retrieve_body( $request );
		$domains = json_decode( $body, true );
		$results = array();

		if ( empty( $domains ) ) {
			return $no_results;
		}

		foreach ( $domains as $domain ) {
			if ( empty( $domain['verified_at'] ) ) {
				continue;
			}

			$results[] = $domain['domain'];
		}

		if ( empty( $results ) ) {
			return $no_results;
		}

		return $results;
	}

}
