<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;

/**
 * Connector for Generic/Custom SMTP integration.
 *
 * @since 1.0
 */
class Connector_Generic extends Connector_Base {

	const SETTING_HOST            = 'host';
	const SETTING_PORT            = 'port';
	const SETTING_AUTH            = 'auth';
	const SETTING_USERNAME        = 'username';
	const SETTING_PASSWORD        = 'password';
	const SETTING_ENCRYPTION_TYPE = 'encryption_type';
	const SETTING_AUTO_TLS        = 'auto_tls';
	const SETTING_USE_RETURN_PATH = 'use_return_path';

	protected $name        = 'generic';
	protected $title       = 'Custom SMTP';
	protected $disabled    = false;
	protected $description = '';
	protected $logo        = 'CustomSMTP';
	protected $full_logo   = 'CustomSMTPFull';

	public function get_description() {
		return __( "Use our Custom SMTP feature to easily connect to any SMTP server. If you don't want to use one of Gravity SMTP's built-in integrations, with Custom SMTP you can sync with a huge array of services that can reliably send your site's emails. For more information on how to get started with Custom SMTP, read our documentation.", 'gravitysmtp' );
	}

	protected $sensitive_fields = array(
		self::SETTING_PASSWORD,
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
			/**
			 * @var Recipient_Collection $to
			 */
			$to          = $this->get_att( 'to', '' );
			$subject     = $this->get_att( 'subject', '' );
			$message     = $this->get_att( 'message', '' );
			$headers     = $this->get_parsed_headers( $this->get_att( 'headers', array() ) );
			$attachments = $this->get_att( 'attachments', array() );
			$from        = $this->get_from( true );
			$reply_to    = $this->get_reply_to( true );
			$source      = $this->get_att( 'source' );
			$params      = array( 'body' => array( __( 'Body is not stored for Custom SMTP events.', 'gravitysmtp' ) ), 'headers' => $headers );
			$email       = $this->email;

			if ( ! empty( $headers['content-type'] ) ) {
				$headers['content-type'] = $this->get_att( 'content_type', $headers['content-type'] );
			}

			$this->set_email_log_data( $subject, $message, $to, empty( $from['name'] ) ? $from['email'] : sprintf( '%s <%s>', $from['name'], $from['email'] ), $headers, $attachments, $source, $params );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Starting email send with Custom SMTP connector.' ) );

			$this->reset_phpmailer();
			$this->configure_phpmailer();

			$this->php_mailer->setFrom( $from['email'], empty( $from['name'] ) ? '' : $from['name'] );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, sprintf( 'Using From Name: %s, From Email: %s', empty( $from['name'] ) ? '' : $from['name'], $from['email'] ) ) );

			foreach( $to->recipients() as $recipient ) {
				if ( ! empty( $recipient->name() ) ) {
					$this->php_mailer->addAddress( $recipient->email(), $recipient->name() );
				} else {
					$this->php_mailer->addAddress( $recipient->email() );
				}
			}

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email recipients: ' . json_encode( $to->as_array() ) ) );

			$this->php_mailer->Subject = $subject;

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email subject: ' . $subject  ) );

			$this->php_mailer->Body = $message;

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email message: ' . esc_html( $message )  ) );

			if ( ! empty( $headers['cc'] ) ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email CC: ' . json_encode( $headers['cc']->as_array() ) ) );

				foreach ( $headers['cc']->recipients() as $recipient ) {
					if ( ! empty( $recipient->name() ) ) {
						$this->php_mailer->addCC( $recipient->email(), $recipient->name() );
					} else {
						$this->php_mailer->addCC( $recipient->email() );
					}
				}
			}

			if ( ! empty( $headers['bcc'] ) ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email BCC: ' . json_encode( $headers['bcc']->as_array() ) ) );

				foreach ( $headers['bcc']->recipients() as $recipient ) {
					if ( ! empty( $recipient->name() ) ) {
						$this->php_mailer->addBCC( $recipient->email(), $recipient->name() );
					} else {
						$this->php_mailer->addBCC( $recipient->email() );
					}
				}
			}

			if ( ! empty( $attachments ) ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email attachments: ' . json_encode( $attachments ) ) );

				foreach ( $attachments as $attachment ) {
					$this->php_mailer->addAttachment( $attachment );
				}
			}

			if ( ! empty( $reply_to ) ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email reply_to: ' . json_encode( $reply_to ) ) );

				foreach( $reply_to as $address ) {
					if ( isset( $address['name'] ) ) {
						$this->php_mailer->addReplyTo( $address['email'], $address['name'] );
					} else {
						$this->php_mailer->addReplyTo( $address['email'] );
					}
				}

			}

			if ( ! empty( $headers['content-type'] ) && strpos( $headers['content-type'], 'text/html' ) !== false ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Setting content type to text/html' ) );
				$this->php_mailer->isHTML( true );
			} else {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Setting content type to text/plain' ) );
				$this->php_mailer->isHTML( false );
				$this->php_mailer->ContentType = 'text/plain';
			}

			$additional_headers = $this->get_filtered_message_headers();

			if ( ! empty( $additional_headers ) ) {
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Additional email headers: ' . json_encode( $additional_headers ) ) );

				foreach ( $additional_headers as $key => $value ) {
					$this->php_mailer->addCustomHeader( $key, $value );
				}
			}

			if ( (bool) $this->get_setting( self::SETTING_USE_RETURN_PATH, false ) ) {
				$this->php_mailer->Sender = $this->php_mailer->From;
			}

			$this->logger->log( $email, 'pre_send', array(
				self::SETTING_AUTH => $this->php_mailer->SMTPAuth,
				'secure'           => $this->php_mailer->SMTPSecure,
				self::SETTING_HOST => $this->php_mailer->Host,
				self::SETTING_PORT => $this->php_mailer->Port,
			) );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'SMTP Connection Details: ' . json_encode( array(
					self::SETTING_AUTH => $this->php_mailer->SMTPAuth,
					'secure'           => $this->php_mailer->SMTPSecure,
					self::SETTING_HOST => $this->php_mailer->Host,
					self::SETTING_PORT => $this->php_mailer->Port,
				) ) ) );

			if ( $this->is_test_mode() ) {
				$this->events->update( array( 'status' => 'sandboxed' ), $email );
				$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Test mode is enabled, sandboxing email.' ) );

				return true;
			}

			/**
			 * Fires after PHPMailer is initialized.
			 *
			 * @since 2.2.0
			 *
			 * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
			 */
			do_action_ref_array( 'phpmailer_init', array( &$this->php_mailer ) );

			$this->php_mailer->send();

			$this->events->update( array( 'status' => 'sent' ), $email );

			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email successfully sent.' ) );

			return true;

		} catch ( \Exception $e ) {
			$this->events->update( array( 'status' => 'failed' ), $email );

			$this->logger->log( $email, 'failed', $this->php_mailer->ErrorInfo );

			$this->debug_logger->log_error( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email failed to send. Details: ' . $this->php_mailer->ErrorInfo ) );

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
		return array();
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
			self::SETTING_HOST             => $this->get_setting( self::SETTING_HOST, '' ),
			self::SETTING_PORT             => $this->get_setting( self::SETTING_PORT, '' ),
			self::SETTING_AUTH             => $this->get_setting( self::SETTING_AUTH, false ),
			self::SETTING_USERNAME         => $this->get_setting( self::SETTING_USERNAME, '' ),
			self::SETTING_PASSWORD         => $this->get_setting( self::SETTING_PASSWORD, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			self::SETTING_ENCRYPTION_TYPE  => $this->get_setting( self::SETTING_ENCRYPTION_TYPE, 'tls' ),
			self::SETTING_AUTO_TLS         => (bool) $this->get_setting( self::SETTING_AUTO_TLS, false ),
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
		$encryption_type = $this->get_setting( self::SETTING_ENCRYPTION_TYPE, 'tls' );

		return array(
			'title'       => esc_html__( 'Custom SMTP Settings', 'gravitysmtp' ),
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
//								'label' => esc_html__( 'If enabled, Custom SMTP will be the default SMTP mailer.', 'gravitysmtp' ),
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
							'helpTextAttributes' => array(
								'content' => esc_html__( 'The URL (such as smtp.mailprovider.com) or IP address of your SMTP host.', 'gravitysmtp' ),
								'spacing' => [ 2, 0, 0, 0 ],
							),
							'labelAttributes'    => array(
								'label' => esc_html__( 'SMTP Hostname', 'gravitysmtp' ),
							),
							'name'               => self::SETTING_HOST,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_HOST, '' ),
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'Port 465 is usually used with SSL. Ports 25 and 587 are usually used with TLS.', 'gravitysmtp' ),
								'spacing' => [ 2, 0, 0, 0 ],
							),
							'labelAttributes'    => array(
								'label' => esc_html__( 'SMTP Port', 'gravitysmtp' ),
							),
							'name'               => self::SETTING_PORT,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_PORT, '' ),
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
						'component' => 'Toggle',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'SMTP servers usually use TLS if available. However, on some servers, you may need to disable it to prevent issues.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
								'spacing' => [ 2, 0, 0, 0 ],
							),
							'helpTextWidth'      => 'full',
							'initialChecked'     => (bool) $this->get_setting( self::SETTING_AUTO_TLS, false ),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Auto TLS', 'gravitysmtp' ),
							),
							'labelPosition'      => 'left',
							'name'               => self::SETTING_AUTO_TLS,
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
									'label'   => __( 'Encryption', 'gravitysmtp' ),
									//'htmlFor' => self::SETTING_ENCRYPTION_TYPE,
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
									'id'               => self::SETTING_ENCRYPTION_TYPE . '_group',
									'initialValue'     => $encryption_type,
									'inputType'        => 'radio',
									'spacing'          => 2,
									'data'             => array(
										array(
											'id'              => self::SETTING_ENCRYPTION_TYPE . '_tls',
											'name'            => self::SETTING_ENCRYPTION_TYPE,
											'value'           => 'tls',
											'size'            => 'size-md',
											'spacing'         => array( 0, 4, 0, 0 ),
											'labelAttributes' => array(
												'label'  => __( 'TLS', 'gravitysmtp' ),
												'size'   => 'text-sm',
												'weight' => 'regular',
											),
										),
										array(
											'id'              => self::SETTING_ENCRYPTION_TYPE . '_ssl',
											'name'            => self::SETTING_ENCRYPTION_TYPE,
											'value'           => 'ssl',
											'size'            => 'size-md',
											'spacing'         => array( 0, 4, 0, 0 ),
											'labelAttributes' => array(
												'label'  => __( 'SSL', 'gravitysmtp' ),
												'size'   => 'text-sm',
												'weight' => 'regular',
											),
										),
										array(
											'id'              => self::SETTING_ENCRYPTION_TYPE . '_none',
											'name'            => self::SETTING_ENCRYPTION_TYPE,
											'value'           => 'none',
											'size'            => 'size-md',
											'spacing'         => array( 0, 4, 0, 0 ),
											'labelAttributes' => array(
												'label'  => __( 'None', 'gravitysmtp' ),
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
									'size'    => 'text-xs',
									'weight'  => 'regular',
									'content' => esc_html__( 'In most cases, TLS is the preferred encryption method.', 'gravitysmtp' ),
								),
							)
						),
					),
					array(
						'component' => 'Toggle',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'Enable authentication if your SMTP server requires a username and password. This option should be enabled in most cases.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
								'spacing' => [ 2, 0, 0, 0 ],
							),
							'helpTextWidth'      => 'full',
							'initialChecked'     => (bool) $this->get_setting( self::SETTING_AUTH, false ),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Authentication', 'gravitysmtp' ),
							),
							'labelPosition'      => 'left',
							'name'               => self::SETTING_AUTH,
							'size'               => 'size-m',
							'spacing'            => 5,
							'width'              => 'full',
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'The username for logging into your mail server.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Authentication Username', 'gravitysmtp' ),
							),
							'name'               => self::SETTING_USERNAME,
							'size'               => 'size-l',
							'spacing'            => 6,
							'value'              => $this->get_setting( self::SETTING_USERNAME, '' ),
						),
					),
					array(
						'component' => 'Input',
						'props'     => array(
							'helpTextAttributes' => array(
								'content' => esc_html__( 'The password for accessing your mail server. It will be stored securely in the database.', 'gravitysmtp' ),
								'size'    => 'text-xs',
								'weight'  => 'regular',
							),
							'customAttributes'   => array(
								'style' => array(
									'display' => 'block',
									'width'   => '100%',
								),
							),
							'labelAttributes'    => array(
								'label' => esc_html__( 'Authentication Password', 'gravitysmtp' ),
							),
							'name'               => self::SETTING_PASSWORD,
							'size'               => 'size-l',
							'spacing'            => 6,
							'type'               => 'password',
							'value'              => $this->get_setting( self::SETTING_PASSWORD, '' ),
						),
					),
				),
				$this->get_from_settings_fields(),
			),
		);
	}

	/**
	 * Determine if the SMTP credentials are configured correctly.
	 *
	 * @since 1.0
	 *
	 * @return bool|\WP_Error
	 */
	public function is_configured() {

		// Protect against other plugins modifying PHPMailer.
		if ( ! class_exists( 'PHPMailer\PHPMailer\SMTP' ) ) {
			$error = new \WP_Error( 'invalid_configuration', __( 'PHPMailer is not configured on this system.', 'gravitysmtp' ) );

			return $error;
		}

		$this->configure_phpmailer();

		try {
			// @todo - this can be adjusted if we find it's causing correct-but-slow configurations to fail.
			$this->php_mailer->Timeout = 10;
			$this->php_mailer->smtpConnect();
		} catch ( \Exception $e ) {
			$error            = new \WP_Error( 'invalid_configuration', $e->getMessage() );
			self::$configured = $error;

			return $error;
		}

		self::$configured = true;

		return true;
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
	 * Configure the PHPMailer instance.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private function configure_phpmailer() {
		$this->php_mailer->isSMTP();
		$this->php_mailer->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
		$this->php_mailer->Host    = $this->get_setting( self::SETTING_HOST, '' );
		$this->php_mailer->Port    = $this->get_setting( self::SETTING_PORT, '' );

		if ( (bool) $this->get_setting( self::SETTING_AUTH ) ) {
			$this->php_mailer->SMTPAuth = true;
			$this->php_mailer->Username = $this->get_setting( self::SETTING_USERNAME, '' );
			$this->php_mailer->Password = $this->get_setting( self::SETTING_PASSWORD, '' );
		}

		$this->php_mailer->SMTPSecure  = $this->get_setting( self::SETTING_ENCRYPTION_TYPE, 'tls' );
		$this->php_mailer->SMTPAutoTLS = (bool) $this->get_setting( self::SETTING_AUTO_TLS, false );
	}

}
