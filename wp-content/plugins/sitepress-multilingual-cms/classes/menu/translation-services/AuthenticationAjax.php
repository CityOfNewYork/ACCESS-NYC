<?php

namespace WPML\TM\Menu\TranslationServices;

use TranslationProxy;
use TranslationProxy_Service;
use WPML\TM\TranslationProxy\Services\AuthorizationFactory;
use WPMLTranslationProxyApiException;

class AuthenticationAjax {

	const AJAX_ACTION = 'translation_service_authentication';

	/** @var  AuthorizationFactory */
	protected $authorize_factory;

	/**
	 * @param AuthorizationFactory $authorize_factory
	 */
	public function __construct( AuthorizationFactory $authorize_factory ) {
		$this->authorize_factory = $authorize_factory;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_translation_service_authentication', [ $this, 'authenticate_service' ] );
		add_action( 'wp_ajax_translation_service_update_credentials', [ $this, 'update_credentials' ] );
		add_action( 'wp_ajax_translation_service_enable_unlisted_service', [ $this, 'enable_unlisted_service' ] );
		add_action( 'wp_ajax_translation_service_invalidation', [ $this, 'invalidate_service' ] );
	}

	/**
	 * @return void
	 */
	public function authenticate_service() {
		$this->handle_action(
			function () {
				$this->authorize_factory->create()->authorize(
					json_decode( stripslashes( $_POST['custom_fields'] ) )
				);
			},
			[ $this, 'is_valid_request_with_params' ],
			__( 'Service activated.', 'wpml-translation-management' ),
			__(
				'The authentication didn\'t work. Please make sure you entered your details correctly and try again.',
				'wpml-translation-management'
			)
		);
	}

	/**
	 * @return void
	 */
	public function update_credentials() {
		$this->handle_action(
			function () {
				$this->authorize_factory->create()->updateCredentials(
					json_decode( stripslashes( $_POST['custom_fields'] ) )
				);
			},
			[ $this, 'is_valid_request_with_params' ],
			__( 'Service credentials updated.', 'wpml-translation-management' ),
			__(
				'The authentication didn\'t work. Please make sure you entered your details correctly and try again.',
				'wpml-translation-management'
			)
		);
	}

	public function enable_unlisted_service() {
		if ( ! $this->is_valid_nonce_request() ) {
			$this->send_error( __( 'Invalid Request', 'sitepress' ), __( 'Reload the page and try again.', 'sitepress' ) );
		}

		$suid = isset( $_POST['suid'] ) ? sanitize_text_field( $_POST['suid'] ) : '';

		if ( ! $suid ) {
			$this->send_error( __( 'Invalid Request', 'sitepress' ), __( 'The field can\'t be empty.', 'sitepress' ) );

			return;
		}

		try {
			$service = TranslationProxy_Service::get_service_by_suid( $suid );
		} catch ( WPMLTranslationProxyApiException $e ) {
			$this->send_error(
				__( 'We couldn\'t find a translation service connected to this key.', 'sitepress' ),
				__( 'Please contact your translation service and ask them to provide you with this information.', 'sitepress' )
			);

			return;
		}

		$result = \WPML\TM\Menu\TranslationServices\Endpoints\Select::select( $service->id );

		if ( is_string( $result ) ) { // In case of an error, the result will return a string.
			$this->send_error( __( 'Error Server', 'sitepress' ), __( 'Unable to set this service as default.', 'sitepress' ) );

			return;
		}
		$this->send_success_response( __( 'Service added and set as default.', 'sitepress' ) );
	}

	/**
	 * @return void
	 */
	public function invalidate_service() {
		$this->handle_action(
			function () {
				$this->authorize_factory->create()->deauthorize();
			},
			[ $this, 'is_valid_nonce_request' ],
			__( 'Service invalidated.', 'wpml-translation-management' ),
			__( 'Unable to invalidate this service. Please contact WPML support.', 'wpml-translation-management' )
		);
	}

	/**
	 * @param callable $action
	 * @param callable $request_validation
	 * @param string   $success_message
	 * @param string   $failure_message
	 *
	 * @return void
	 */
	private function handle_action(
		callable $action,
		callable $request_validation,
		$success_message,
		$failure_message
	) {
		if ( $request_validation() ) {
			try {
				$action();

				$this->send_success_response( $success_message );
			} catch ( \Exception $e ) {
				return $this->send_error_message( $failure_message );
			}
		} else {
			$this->send_error_message( __( 'Invalid Request', 'wpml-translation-management' ) );
		}
	}

	/**
	 * @param string $msg
	 *
	 * @return void
	 */
	private function send_success_response( $msg ) {
		wp_send_json_success(
			[
				'errors'  => 0,
				'message' => $msg,
				'reload'  => 1,
			]
		);
	}

	/**
	 * @param string $msg
	 *
	 * @return bool
	 */
	private function send_error_message( $msg ) {
		wp_send_json_error(
			[
				'errors'  => 1,
				'message' => $msg,
				'reload'  => 0,
			]
		);
	}

	private function send_error( $title, $description ) {
		wp_send_json_error(
			[
				'errors'      => 1,
				'title'       => $title,
				'description' => $description,
				'reload'      => 0,
			],
			400
		);
	}

	/**
	 * @return bool
	 */
	public function is_valid_nonce_request() {
		return isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], self::AJAX_ACTION );
	}

	/**
	 * @return bool
	 */
	public function is_valid_request_with_params() {
		return isset( $_POST['service_id'], $_POST['custom_fields'] ) && $this->is_valid_nonce_request();
	}
}
