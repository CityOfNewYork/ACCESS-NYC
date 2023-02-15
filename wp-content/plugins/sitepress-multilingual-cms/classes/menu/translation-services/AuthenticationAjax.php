<?php

namespace WPML\TM\Menu\TranslationServices;

use WPML\TM\TranslationProxy\Services\AuthorizationFactory;

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
		add_action( 'wp_ajax_translation_service_invalidation', [ $this, 'invalidate_service' ] );
	}

	/**
	 * @return bool
	 */
	public function authenticate_service() {
		return $this->handle_action(
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
	 * @return bool
	 */
	public function update_credentials() {
		return $this->handle_action(
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

	/**
	 * @return bool
	 */
	public function invalidate_service() {
		return $this->handle_action(
			function () {
				$this->authorize_factory->create()->deauthorize();
			},
			[ $this, 'is_valid_request' ],
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
	 * @return bool
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

				return $this->send_success_response( $success_message );
			} catch ( \Exception $e ) {
				return $this->send_error_message( $failure_message );
			}
		} else {
			return $this->send_error_message( __( 'Invalid Request', 'wpml-translation-management' ) );
		}
	}

	/**
	 * @param string $msg
	 *
	 * @return bool
	 */
	private function send_success_response( $msg ) {
		wp_send_json_success(
			[
				'errors'  => 0,
				'message' => $msg,
				'reload'  => 1,
			]
		);

		return true;
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

		return false;
	}

	/**
	 * @return bool
	 */
	public function is_valid_request() {
		return isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], self::AJAX_ACTION );
	}

	/**
	 * @return bool
	 */
	public function is_valid_request_with_params() {
		return isset( $_POST['service_id'], $_POST['custom_fields'] ) && $this->is_valid_request();
	}
}
