<?php
namespace EnableMediaReplace;

use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Api as Api;
use EnableMediaReplace\ApiKeyManager as ApiKeyManager;

class Ajax {
	public function __construct() {
		$endpoints = array(
			'remove_background',
			'save_api_key',
			'delete_api_key',
		);
		foreach ( $endpoints as $action ) {
			add_action( "wp_ajax_emr_{$action}", array( $this, $action ) );
		}
	}

	public function remove_background() {
		if ( $this->check_nonce() ) {
			$api = new Api;
			$response = $api->request( $_POST );
            wp_send_json($response);
		}
		else {
				die('Wrong nonce');
		}
	}

	public function save_api_key() {
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'enable-media-replace' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'emr_save_api_key' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token. Please reload the page and try again.', 'enable-media-replace' ) ) );
		}

		$key = isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : '';
		$result = ApiKeyManager::getInstance()->saveAndVerify( $key );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		}
		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	public function delete_api_key() {
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'enable-media-replace' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'emr_save_api_key' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token. Please reload the page and try again.', 'enable-media-replace' ) ) );
		}

		ApiKeyManager::getInstance()->deleteKey();
		wp_send_json_success( array( 'message' => __( 'API Key removed.', 'enable-media-replace' ) ) );
	}

	private function check_nonce() {
		$nonce  = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
		return wp_verify_nonce( $nonce, $action );
	}
}


new Ajax();