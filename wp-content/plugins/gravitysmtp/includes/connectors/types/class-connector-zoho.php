<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Types;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth\Zoho_Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Enums\Zoho_Datacenters_Enum;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

/**
 * Connector for Zoho
 *
 * @since 1.0
 */
class Connector_Zoho extends Connector_Base {

	const SETTING_ACCESS_TOKEN       = 'access_token';
	const SETTING_CLIENT_ID          = 'client_id';
	const SETTING_CLIENT_SECRET      = 'client_secret';
	const SETTING_DATA_CENTER_REGION = 'data_center_region';
	const SETTING_ACCOUNT_ID         = 'account_id';

	const VALUE_REDIRECT_URI      = 'redirect_uri';
	const VALUE_REDIRECT_URI_FULL = 'redirect_uri_full';

	protected $name      = 'zoho';
	protected $title     = 'Zoho Mail';
	protected $logo      = 'Zoho';
	protected $full_logo = 'ZohoFull';

	protected $sensitive_fields = array(
		self::SETTING_ACCESS_TOKEN,
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
		$atts   = $this->get_send_atts();
		$email  = $this->email;
		$source = $this->get_att( 'source' );
		$params = $this->get_request_body( $atts );

		$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['email'], $atts['headers'], $atts['attachments'], $source, $params );

		$args = array(
			'headers' => $this->get_request_headers(),
			'body'    => json_encode( $params ),
		);

		$this->set_email_log_data( $atts['subject'], $atts['message'], $atts['to'], $atts['from']['from'], $atts['headers'], $atts['attachments'], $source, $params );

		$this->logger->log( $email, 'started', __( 'Starting email send for Zoho connector.', 'gravitysmtp' ) );

		$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Starting email send with Zoho connector and the following params: ' . json_encode( $params ) ) );

		if ( $this->is_test_mode() ) {
			$this->events->update( array( 'status' => 'sandboxed' ), $email );
			$this->logger->log( $email, 'sandboxed', __( 'Email sandboxed.', 'gravitysmtp' ) );
			$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Test mode is enabled, sandboxing email.' ) );

			return true;
		}

		$request_url   = $this->get_api_url( sprintf( 'api/accounts/%s/messages', $this->get_setting( self::SETTING_ACCOUNT_ID, '' ) ) );
		$request       = wp_remote_post( $request_url, $args );
		$response_code = wp_remote_retrieve_response_code( $request );
		$body          = wp_remote_retrieve_body( $request );
		$decoded       = json_decode( $body, true );

		if ( $response_code >= 300 ) {
			$this->log_failure( $email, $body );
			$this->debug_logger->log_error( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email failed to send. Details: ' . $body ) );

			return $email;
		}

		$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Received response of: ' . $body ) );

		$this->events->update( array( 'status' => 'sent' ), $email );
		$this->logger->log( $email, 'sent', __( 'Email successfully sent.', 'gravitysmtp' ) );
		$this->debug_logger->log_debug( $this->wrap_debug_with_details( __FUNCTION__, $email, 'Email successfully sent.' ) );

		return true;
	}

	private function get_request_headers() {
		return array(
			'Authorization' => 'Zoho-oauthtoken ' . $this->get_setting( self::SETTING_ACCESS_TOKEN ),
			'Content-Type' => 'application/json',
		);
	}

	private function get_request_body( $atts ) {
		$body = array(
			'fromAddress' => $atts['from']['email'],
			'toAddress'   => $atts['to']->first()->email,
			'subject'     => $atts['subject'],
			'content'     => $atts['message'],
			'encoding'    => 'UTF-8',
		);

		$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

		$body['mailFormat'] = $is_html ? 'html' : 'plaintext';

		if ( ! empty( $atts['attachments'] ) ) {
			$body['attachments'] = $this->handle_attachments( $atts['attachments'] );
		}

		return $body;
	}

	private function handle_attachments( $attachments ) {
		$data = [];

		$headers                 = $this->get_request_headers();
		$headers['Content-Type'] = 'application/octet-stream';

		foreach ( $attachments as $attachment ) {
			if ( ! file_exists( $attachment ) ) {
				continue;
			}

			$file = file_get_contents( $attachment );

			if ( $file === false ) {
				continue;
			}

			$file_name = basename( $attachment );

			// Upload the attachment via Zoho API.
			$url = add_query_arg(
				'fileName',
				$file_name,
				$this->get_api_url( sprintf( 'api/accounts/%s/messages/attachments', $this->get_setting( self::SETTING_ACCOUNT_ID, '' ) ) )
			);

			$params = array(
				'headers' => $headers,
				'body'    => $file,
			);

			$response = wp_safe_remote_post( $url, $params );

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				continue;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $body['data'] ) ) {
				$data[] = $body['data'];
			}
		}

		return $data;
	}

	/**
	 * Get the attributes for sending email.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_send_atts() {
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

	private function get_api_url( $endpoint ) {
		$data_center_location = $this->get_setting( self::SETTING_DATA_CENTER_REGION, 'us' );

		$base = Zoho_Datacenters_Enum::url_for_datacenter( $data_center_location );

		return trailingslashit( $base ) . $endpoint;
	}

	public function get_description() {
		return esc_html__( 'Reach inboxes when it matters most. Send email notifications to your contacts with Zoho Mail.', 'gravitysmtp' );
	}


	protected function get_merged_data() {
		$data             = parent::get_merged_data();
		$data['disabled'] = ! Feature_Flag_Manager::is_enabled( 'zoho_integration' );

		return $data;
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
			self::SETTING_ACCESS_TOKEN     => $this->get_setting( self::SETTING_ACCESS_TOKEN, '' ),
			self::SETTING_FROM_EMAIL       => $this->get_setting( self::SETTING_FROM_EMAIL, '' ),
			self::SETTING_FORCE_FROM_EMAIL => $this->get_setting( self::SETTING_FORCE_FROM_EMAIL, false ),
			self::SETTING_FROM_NAME        => $this->get_setting( self::SETTING_FROM_NAME, '' ),
			self::SETTING_FORCE_FROM_NAME  => $this->get_setting( self::SETTING_FORCE_FROM_NAME, false ),
			'oauth_url'                    => 'https://accounts.zoho.com/oauth/v2/auth',
			'oauth_params'                 => '&' . $this->get_oauth_params(),
		);
	}

	protected function get_oauth_params() {
		/**
		 * @var Zoho_Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::ZOHO_OAUTH_HANDLER );

		$params = array(
			'response_type' => 'code',
			'redirect_uri'  => urldecode( $oauth_handler->get_return_url() ),
			'scope'         => $oauth_handler->get_scope(),
			'state'         => 1,
			'access_type'   => 'offline',
			'prompt'        => 'consent',
		);

		return http_build_query( $params );
	}

	public function is_configured() {
		if ( Booliesh::get( $this->get_setting( 'access_token', false ) ) ) {
			/**
			 * @var Zoho_Oauth_Handler $oauth_handler
			 */
			$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::ZOHO_OAUTH_HANDLER );
			$token         = $oauth_handler->get_access_token();

			return $token;
		}

		return false;
	}

	/**
	 * Settings fields.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function settings_fields() {
		/**
		 * @var Zoho_Oauth_Handler $oauth_handler
		 */
		$oauth_handler = Gravity_SMTP::container()->get( Connector_Service_Provider::ZOHO_OAUTH_HANDLER );

		$oauth_handler->handle_response( $this->name );

		$token     = $oauth_handler->get_access_token( $this->name );
		$has_token = $token && ! is_wp_error( $token );

		$settings = array(
			'title'     => esc_html__( 'Zoho Mail Settings', 'gravitysmtp' ),
			'hide_save' => ( ! $has_token ),
			'fields'    => array(),
		);

		if ( ! $token || is_wp_error( $token ) ) {
			$settings['fields'][] = array(
				'component' => 'Alert',
				'props'     => array(
					'customIconPrefix' => 'gravitysmtp-admin-icon',
					'theme'            => 'cosmos',
					'type'             => 'notice',
					'spacing'          => 3,
				),
				'fields'    => array(
					array(
						'component' => 'Text',
						'props'     => array(
							'content'       => esc_html__( 'Please click the button below to initiate a connection with your Zoho Mail account. Remember to fill out both the Client ID and Client Secret fields before proceeding.', 'gravitysmtp' ),
							'customClasses' => array(
								'gform--display-block',
								'gravitysmtp-integration__notice-message'
							),
							'size'          => 'text-sm',
							'spacing'       => 4,
							'tagName'       => 'span',
						),
					),
					array(
						'component' => 'Link',
						'props'     => array(
							'content'       => esc_html__( 'Read our Zoho Mail documentation', 'gravitysmtp' ),
							'customClasses' => array(
								'gform-link--theme-cosmos',
								'gravitysmtp-integration__notice-link',
								'gform-button',
								'gform-button--size-height-m',
								'gform-button--white',
								'gform-button--width-auto'
							),
							'href'          => 'https://docs.gravitysmtp.com/category/integrations/zoho/',
							'target'        => '_blank',
						),
					),
				),
			);
		}

		if ( isset( $_GET['code'] ) && ! $has_token ) {
			$settings['fields'][] = array(
				'component' => 'Alert',
				'props'     => array(
					'customIconPrefix' => 'gravitysmtp-admin-icon',
					'theme'            => 'cosmos',
					'type'             => 'error',
					'spacing'          => 3,
				),
				'fields'    => array(
					array(
						'component' => 'Text',
						'props'     => array(
							'content' => esc_html__( 'Error Connecting to Zoho Mail. Check your credentials and try again.', 'gravitysmtp' ),
							'weight'  => 'medium',
							'size'    => 'text-sm',
							'spacing' => 2,
							'tagName' => 'span',
						),
					),
				),
			);
		}

		$settings['fields'][] = array(
			'component' => 'Heading',
			'props'     => array(
				'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
				'size'    => 'text-sm',
				'spacing' => [ 4, 0, 3, 0 ],
				'tagName' => 'h3',
				'type'    => 'boxed',
				'weight'  => 'medium',
			),
		);

		$settings['fields'][] = array(
			'component' => 'Select',
			'props'     => array(
				'name'               => self::SETTING_DATA_CENTER_REGION,
				'size'               => 'size-l',
				'spacing'            => [ 4, 0, 3, 0 ],
				'helpTextAttributes' => array(
					'content' => esc_html__( 'If you are unsure about your Datacenter location, check your Zoho account.', 'gravitysmtp' ),
					'size'    => 'text-xs',
					'weight'  => 'regular',
				),
				'labelAttributes'    => array(
					'label'  => esc_html__( 'Datacenter Region', 'gravitysmtp' ),
					'size'   => 'text-sm',
					'weight' => 'medium',
				),
				'options'            => Zoho_Datacenters_Enum::select_component_options(),
				'initialValue'       => $this->get_setting( self::SETTING_DATA_CENTER_REGION, 'us' ),
			),
		);

		if ( ! $token || is_wp_error( $token ) ) {
			$settings['fields'][] = array(
				'component'     => 'LinkedHelpTextInput',
				'external'      => true,
				'handle_change' => true,
				'links'         => array(
					array(
						'key'   => 'link',
						'props' => array(
							'href'   => 'https://api-console.zoho.com/',
							'size'   => 'text-xs',
							'target' => '_blank',
						),
					),
				),
				'props'         => array(
					'helpTextAttributes' => array(
						'content' => esc_html__( 'To obtain an Client ID from Zoho Mail, login to your {{link}}Zoho API{{link}} dashboard and generate a Client ID.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
					'labelAttributes'    => array(
						'label'  => esc_html__( 'Client ID', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'               => self::SETTING_CLIENT_ID,
					'spacing'            => 4,
					'size'               => 'size-l',
					'value'              => $this->get_setting( self::SETTING_CLIENT_ID, '' ),
				),
			);

			$settings['fields'][] = array(
				'component'     => 'LinkedHelpTextInput',
				'external'      => true,
				'handle_change' => true,
				'links'         => array(
					array(
						'key'   => 'link',
						'props' => array(
							'href'   => 'https://api-console.zoho.com/',
							'size'   => 'text-xs',
							'target' => '_blank',
						),
					),
				),
				'props'         => array(
					'helpTextAttributes' => array(
						'content' => esc_html__( 'To obtain a Client Secret password, log in to your {{link}}Zoho API{{link}} dashboard and generate a new client secret. Then, copy the value into this field.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
					'labelAttributes'    => array(
						'label'  => esc_html__( 'Client Secret', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'               => self::SETTING_CLIENT_SECRET,
					'spacing'            => 6,
					'size'               => 'size-l',
					'value'              => $this->get_setting( self::SETTING_CLIENT_SECRET, '' ),
				),
			);

			$settings['fields'][] = array(
				'component' => 'CopyInput',
				'external'  => true,
				'props'     => array(
					'actionButtonAttributes' => array(
						'customAttributes' => array(
							'type' => 'button',
						),
						'icon'             => 'copy',
						'iconPrefix'       => 'gravitysmtp-admin-icon',
						'label'            => esc_html__( 'Copy', 'gravitysmtp' ),
					),
					'labelAttributes'        => array(
						'label'  => esc_html__( 'Redirect URI', 'gravitysmtp' ),
						'size'   => 'text-sm',
						'weight' => 'medium',
					),
					'name'                   => self::VALUE_REDIRECT_URI,
					'spacing'                => 6,
					'size'                   => 'size-l',
					'customAttributes'       => array(
						'readOnly' => true,
					),
					'value'                  => urldecode( $oauth_handler->get_return_url( 'copy' ) . '?page=gravitysmtp-settings&integration=zoho&tab=integrations' ),
					'helpTextAttributes'     => array(
						'content' => __( 'Copy this URL into the "Authorized redirect URIs" field of your Zoho Mail application.', 'gravitysmtp' ),
						'size'    => 'text-xs',
						'weight'  => 'regular',
					),
				),
			);

			$settings['fields'][] = array(
				'component' => 'Heading',
				'props'     => array(
					'content' => esc_html__( 'Authorization', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'spacing' => [ 4, 0, 3, 0 ],
					'tagName' => 'h3',
					'type'    => 'boxed',
					'weight'  => 'medium',
				),
			);

			$settings['fields'][] = array(
				'component' => 'BrandedButton',
				'external'  => true,
				'props'     => array(
					'label'   => esc_html__( 'Sign in with Zoho Mail', 'gravitysmtp' ),
					'spacing' => 6,
					'Svg'     => 'ZohoAltLogo',
					'type'    => 'color',
				),
			);
		} else {
			$settings['fields'][] = array(
				'component' => 'Box',
				'props'     => array(
					'customClasses' => array( 'gravitysmtp-google-integration__connected-message' ),
					'display'       => 'flex',
					'spacing'       => 3,
				),
				'fields'    => array(
					array(
						'component' => 'Icon',
						'props'     => array(
							'customClasses' => array(
								'gravitysmtp-google-integration__checkmark',
								'gform-icon--preset-active',
								'gform-icon-preset--status-correct',
								'gform-alert__icon'
							),
							'icon'          => 'checkmark-simple',
							'iconPrefix'    => 'gravitysmtp-admin-icon',
						),
					),
					array(
						'component' => 'Text',
						'props'     => array(
							'asHtml'  => true,
							'content' => sprintf(
								'%s <span class="gform-text gform-text--color-port gform-typography--size-text-sm gform-typography--weight-medium">%s</span>',
								esc_html__( 'Connected to Zoho Mail with account:', 'gravitysmtp' ),
								esc_html( $oauth_handler->get_connection_details()['account_id'] )
							),
							'size'    => 'text-sm',
							'tagName' => 'span',
						),
					),
				),
			);

			$disconnect_url = admin_url( 'admin-post.php?action=smtp_disconnect_zoho' );

			$settings['fields'][] = array(
				'component' => 'Text',
				'props'     => array(
					'content' => sprintf( '<a href="%s" class="%s"><span class="gravitysmtp-admin-icon gravitysmtp-admin-icon--x-circle gform-button__icon"></span>%s</a>', $disconnect_url, 'gform-link gform-link--theme-cosmos gform-button gform-button--size-height-m gform-button--white gform-button--width-auto gform-button--active-type-loader gform-button--loader-after gform-button--icon-leading', __( 'Disconnect from Zoho Mail', 'gravitysmtp' ) ),
					'asHtml'  => true,
					'spacing' => 6,
				),
			);

			$settings['fields'][] = array(
				'component' => 'Heading',
				'props'     => array(
					'content' => esc_html__( 'Configuration', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'spacing' => [ 4, 0, 4, 0 ],
					'tagName' => 'h3',
					'type'    => 'boxed',
					'weight'  => 'medium',
				),
			);

			$settings['fields'] = array_merge( $settings['fields'], $this->get_from_settings_fields() );
		}

		return $settings;
	}

	/**
	 * Logs an email send failure.
	 *
	 * @since 1.4.0
	 *
	 * @param string $email         The email that failed.
	 * @param string $error_message The error message.
	 */
	private function log_failure( $email, $error_message ) {
		$this->events->update( array( 'status' => 'failed' ), $email );
		$this->logger->log( $email, 'failed', $error_message );
	}

}
