<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Exception;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Utils\AWS_Signature_Handler;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

/**
 * Connector for Amazon SES
 *
 * @since 1.0
 */
class Connector_Amazon extends Connector_Base {

	const SETTING_CLIENT_ID     = 'access_key_id';
	const SETTING_CLIENT_SECRET = 'secret_access_key';
	const SETTING_REGION        = 'region';

	const REGION_US_EAST_N_VIRGINIA      = 'us-east-1';
	const REGION_US_EAST_OHIO            = 'us-east-2';
	const REGION_US_WEST_N_CALIFORNIA    = 'us-west-1';
	const REGION_US_WEST_OREGON          = 'us-west-2';
	const REGION_AFRICA_CAPE_TOWN        = 'af-south-1';
	const REGION_ASIA_PACIFIC_HONG_KONG  = 'ap-east-1';
	const REGION_ASIA_PACIFIC_JAKARTA    = 'ap-southeast-3';
	const REGION_ASIA_PACIFIC_MUMBAI     = 'ap-south-1';
	const REGION_ASIA_PACIFIC_OSAKA      = 'ap-northeast-3';
	const REGION_ASIA_PACIFIC_SEOUL      = 'ap-northeast-2';
	const REGION_ASIA_PACIFIC_SINGAPORE  = 'ap-southeast-1';
	const REGION_ASIA_PACIFIC_SYDNEY     = 'ap-southeast-2';
	const REGION_ASIA_PACIFIC_TOKYO      = 'ap-northeast-1';
	const REGION_CANADA_CENTRAL          = 'ca-central-1';
	const REGION_EUROPE_FRANKFURT        = 'eu-central-1';
	const REGION_EUROPE_IRELAND          = 'eu-west-1';
	const REGION_EUROPE_LONDON           = 'eu-west-2';
	const REGION_EUROPE_MILAN            = 'eu-south-1';
	const REGION_EUROPE_PARIS            = 'eu-west-3';
	const REGION_EUROPE_STOCKHOLM        = 'eu-north-1';
	const REGION_MIDDLE_EAST_BAHRAIN     = 'me-south-1';
	const REGION_SOUTH_AMERICA_SAO_PAULO = 'sa-east-1';

	const API_ENDPOINT  = '/v2/email/outbound-emails';
	const ISO8601_BASIC = 'Ymd\THis\Z';

	protected $name        = 'amazon';
	protected $title       = 'Amazon SES';
	protected $disabled    = false;
	protected $logo        = 'AmazonAWS';
	protected $full_logo   = 'AmazonAWSFull';

	public function get_description() {
		return __( 'Amazon SES offers a reliable and cost-effective service for sending and receiving emails using your own domain. It leverages Amazon’s robust infrastructure, making it a powerful option for managing your email communication.', 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_CLIENT_ID,
		self::SETTING_CLIENT_SECRET,
	);

	/**
	 * Sending logic.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function send() {
		$to          = $this->get_att( 'to', '' );
		$subject     = $this->get_att( 'subject', '' );
		$message     = $this->get_att( 'message', '' );
		$headers     = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );
		$attachments = $this->get_att( 'attachments', array() );
		$from        = $this->get_from( true );
		$reply_to    = $this->get_reply_to( true );
		$source      = $this->get_att( 'source' );
		$params      = $this->get_request_params();
		$email       = $this->email;

		$this->reset_phpmailer();

		if ( ! empty( $headers['content-type'] ) ) {
			$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
		}

		$this->set_email_log_data( $subject, $message, $to, empty( $from['name'] ) ? $from['email'] : sprintf( '%s <%s>', $from['name'], $from['email'] ), $headers, $attachments, $source, $params );

		$this->logger->log( $email, 'started', __( 'Starting email send for Amazon SES connector.', 'gravitysmtp' ) );

		$this->php_mailer->CharSet = 'UTF-8';

		$this->php_mailer->setFrom( $from['email'], empty( $from['name'] ) ? '' : $from['name'] );

		foreach ( $to->recipients() as $recipient ) {
			if ( ! empty( $recipient->name() ) ) {
				$this->php_mailer->addAddress( $recipient->email(), $recipient->name() );
			} else {
				$this->php_mailer->addAddress( $recipient->email() );
			}
		}

		$this->php_mailer->Subject = $subject;

		$this->php_mailer->Body = $message;

		if ( ! empty( $headers['cc'] ) ) {
			foreach ( $headers['cc']->recipients() as $recipient ) {
				if ( ! empty( $recipient->name() ) ) {
					$this->php_mailer->addCC( $recipient->email(), $recipient->name() );
				} else {
					$this->php_mailer->addCC( $recipient->email() );
				}
			}
		}

		if ( ! empty( $headers['bcc'] ) ) {
			foreach ( $headers['bcc']->recipients() as $recipient ) {
				if ( ! empty( $recipient->name() ) ) {
					$this->php_mailer->addBCC( $recipient->email(), $recipient->name() );
				} else {
					$this->php_mailer->addBCC( $recipient->email() );
				}
			}
		}

		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$this->php_mailer->addAttachment( $attachment );
			}
		}

		if ( ! empty( $reply_to ) ) {
			foreach ( $reply_to as $address ) {
				if ( isset( $address['name'] ) ) {
					$this->php_mailer->addReplyTo( $address['email'], $address['name'] );
				} else {
					$this->php_mailer->addReplyTo( $address['email'] );
				}
			}
		}

		if ( ! empty( $headers['content-type'] ) && strpos( $headers['content-type'], 'text/html' ) !== false ) {
			$this->php_mailer->isHTML( true );
		} else {
			$this->php_mailer->isHTML( false );
			$this->php_mailer->ContentType = 'text/plain';
		}

		$additional_headers = $this->get_filtered_message_headers();

		if ( ! empty( $additional_headers ) ) {
			foreach ( $additional_headers as $key => $value ) {
				$value = str_replace( sprintf( '%s:', $key ), '', $value );
				$this->php_mailer->addCustomHeader( $key, trim( $value ) );
			}
		}

		if ( $this->is_test_mode() ) {
			$this->events->update( array( 'status' => 'sandboxed' ), $email );
			$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );

			return true;
		}

		try {
			$debug_atts = compact( 'to', 'from', 'subject', 'headers', 'source', 'attachments', 'reply_to' );
			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Attempting send with the following attributes: ' . json_encode( $debug_atts ) ) );

			$raw = $this->get_raw_message();

			/**
			 * @var AWS_Signature_Handler $signature_handler
			 */
			$signature_handler = Gravity_SMTP::$container->get( Utils_Service_Provider::AWS_SIGNATURE_HANDLER );

			$body = array(
				'Action'           => 'SendRawEmail',
				'Version'          => '2010-12-01',
				'RawMessage'       => array(
					'Data' => $raw,
				),
			);

			$request_data = $signature_handler->get_request_data( $body, $this->get_setting( self::SETTING_CLIENT_ID, '' ), $this->get_setting( self::SETTING_CLIENT_SECRET, '' ), $this->get_setting( self::SETTING_REGION, self::REGION_US_EAST_N_VIRGINIA ) );

			$response = wp_remote_post( $request_data['url'], array( 'headers' => $request_data['headers'], 'body' => $request_data['body'] ) );
			$code     = wp_remote_retrieve_response_code( $response );

			$is_success = (int) $code === 200;

			if ( ! $is_success ) {
				$this->log_failure( $email, wp_remote_retrieve_body( $response ) );

				return $email;
			}

			$this->events->update( array( 'status' => 'sent' ), $email );
			$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );

			return true;
		} catch ( Exception $e ) {
			$this->log_failure( $email, $e->getMessage() );
			$this->debug_logger->log_fatal( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Failed to send: ' . $e->getMessage() ) );

			return $email;
		}
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.4.0
	 *
	 * @param string $email         the email that failed
	 * @param string $error_message the error message
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

	private function get_raw_message() {
		$this->php_mailer->preSend();
		$raw = $this->php_mailer->getSentMIMEMessage();

		return base64_encode( $raw );
	}

	/**
	 * Reset the PHPMailer instance to prevent carryover from previous send.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function reset_phpmailer() {
		$this->php_mailer->clearCustomHeaders();
		$this->php_mailer->clearAddresses();
		$this->php_mailer->clearBCCs();
		$this->php_mailer->clearCCs();
		$this->php_mailer->clearAllRecipients();
		$this->php_mailer->clearReplyTos();
		$this->php_mailer->clearAttachments();
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
			self::SETTING_CLIENT_ID        => $this->get_setting( self::SETTING_CLIENT_ID, '' ),
			self::SETTING_CLIENT_SECRET    => $this->get_setting( self::SETTING_CLIENT_SECRET, '' ),
			self::SETTING_REGION           => $this->get_setting( self::SETTING_REGION, self::REGION_US_EAST_N_VIRGINIA ),
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
			'title'       => esc_html__( 'Amazon SES Settings', 'gravitysmtp' ),
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
						'component'     => 'LinkedHelpTextInput',
						'external'      => true,
						'handle_change' => true,
						'links'         => array(
							array(
								'key'   => 'link',
								'props' => array(
									'href'   => 'https://aws.amazon.com/console/',
									'size'   => 'text-xs',
									'target' => '_blank',
								),
							),
						),
						'props'         => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'Log in to your {{link}}AWS Console{{link}}, go to the IAM dashboard, and create a new access key for an IAM user with AmazonSESFullAccess and AmazonSNSFullAccess permissions.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'labelAttributes'    => array(
								'label'  => esc_html__( 'Access Key ID', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'name'               => self::SETTING_CLIENT_ID,
							'spacing'            => 4,
							'size'               => 'size-l',
							'value'              => $this->get_setting( self::SETTING_CLIENT_ID, '' ),
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'labelAttributes' => array(
								'label'  => esc_html__( 'Secret Access Key', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'name'            => self::SETTING_CLIENT_SECRET,
							'size'            => 'size-l',
							'spacing'         => 6,
							'value'           => $this->get_setting( self::SETTING_CLIENT_SECRET, '' ),
						),
					),
					array(
						'component' => 'Select',
						'props'     => array(
							'labelAttributes' => array(
								'label'  => esc_html__( 'Region', 'gravitysmtp' ),
								'size'   => 'text-sm',
								'weight' => 'medium',
							),
							'name'            => self::SETTING_REGION,
							'size'            => 'size-l',
							'spacing'         => 6,
							'initialValue'    => $this->get_setting( self::SETTING_REGION, self::REGION_US_EAST_N_VIRGINIA ),
							'options'         => $this->get_region_setting_options(),
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

	protected function get_region_setting_options() {
		$region_options = array(
			__( 'US East (N. Virginia)', 'gravitysmtp' )     => self::REGION_US_EAST_N_VIRGINIA,
			__( 'US East (Ohio)', 'gravitysmtp' )            => self::REGION_US_EAST_OHIO,
			__( 'US West (N. California)', 'gravitysmtp' )   => self::REGION_US_WEST_N_CALIFORNIA,
			__( 'US West (Oregon)', 'gravitysmtp' )          => self::REGION_US_WEST_OREGON,
			__( 'Africa (Cape Town)', 'gravitysmtp' )        => self::REGION_AFRICA_CAPE_TOWN,
			__( 'Asia Pacific (Hong Kong)', 'gravitysmtp' )  => self::REGION_ASIA_PACIFIC_HONG_KONG,
			__( 'Asia Pacific (Jakarta)', 'gravitysmtp' )    => self::REGION_ASIA_PACIFIC_JAKARTA,
			__( 'Asia Pacific (Mumbai)', 'gravitysmtp' )     => self::REGION_ASIA_PACIFIC_MUMBAI,
			__( 'Asia Pacific (Osaka)', 'gravitysmtp' )      => self::REGION_ASIA_PACIFIC_OSAKA,
			__( 'Asia Pacific (Seoul)', 'gravitysmtp' )      => self::REGION_ASIA_PACIFIC_SEOUL,
			__( 'Asia Pacific (Singapore)', 'gravitysmtp' )  => self::REGION_ASIA_PACIFIC_SINGAPORE,
			__( 'Asia Pacific (Sydney)', 'gravitysmtp' )     => self::REGION_ASIA_PACIFIC_SYDNEY,
			__( 'Asia Pacific (Tokyo)', 'gravitysmtp' )      => self::REGION_ASIA_PACIFIC_TOKYO,
			__( 'Canada (Central)', 'gravitysmtp' )          => self::REGION_CANADA_CENTRAL,
			__( 'Europe (Frankfurt)', 'gravitysmtp' )        => self::REGION_EUROPE_FRANKFURT,
			__( 'Europe (Ireland)', 'gravitysmtp' )          => self::REGION_EUROPE_IRELAND,
			__( 'Europe (London)', 'gravitysmtp' )           => self::REGION_EUROPE_LONDON,
			__( 'Europe (Milan)', 'gravitysmtp' )            => self::REGION_EUROPE_MILAN,
			__( 'Europe (Paris)', 'gravitysmtp' )            => self::REGION_EUROPE_PARIS,
			__( 'Europe (Stockholm)', 'gravitysmtp' )        => self::REGION_EUROPE_STOCKHOLM,
			__( 'Middle East (Bahrain)', 'gravitysmtp' )     => self::REGION_MIDDLE_EAST_BAHRAIN,
			__( 'South America (São Paulo)', 'gravitysmtp' ) => self::REGION_SOUTH_AMERICA_SAO_PAULO,
		);

		$settings = array();

		foreach ( $region_options as $name => $slug ) {
			$settings[] = array(
				'label' => $name,
				'value' => $slug,
			);
		}

		return $settings;
	}

	/**
	 * Get the unique data for this connector, merged with the default/common data for all
	 * connectors in the system.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] =  ! Feature_Flag_Manager::is_enabled( 'amazon_ses_integration' );

		return $data;
	}

	public function is_configured() {
		if ( ! $this->get_setting( self::SETTING_CLIENT_ID, '' ) || ! $this->get_setting( self::SETTING_CLIENT_SECRET, '' ) ) {
			return false;
		}

		return true;
	}
}
