<?php

class wfRESTAuthenticationController {

	const NONCE_AGE = 600;

	public static function generateNonce($tickOffset = 0) {
		add_filter('nonce_life', 'wfRESTAuthenticationController::nonceAge');

		$i = wp_nonce_tick();
		$salt = wp_salt('nonce');
		$nonce = hash_hmac('sha256', ($i + $tickOffset) . '|wordfence-rest-api-auth', $salt);

		remove_filter('nonce_life', 'wfRESTAuthenticationController::nonceAge');

		return $nonce;
	}

	public static function generateToken() {
		return new wfJWT(wfConfig::get('wordfenceCentralSiteID'));
	}

	public static function nonceAge() {
		return self::NONCE_AGE;
	}

	public function registerRoutes() {
		register_rest_route('wordfence/v1', '/authenticate', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array($this, 'nonce'),
			'permission_callback' => '__return_true',
		));
		register_rest_route('wordfence/v1', '/authenticate', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'authenticate'),
			'permission_callback' => '__return_true',
		));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function nonce($request) {
		$response = rest_ensure_response(array(
			'nonce' => self::generateNonce(),
			'admin_url' => network_admin_url(),
		));
		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function authenticate($request) {
		require_once(WORDFENCE_PATH . '/lib/sodium_compat_fast.php');

		$siteID = wfConfig::get('wordfenceCentralSiteID');
		if (!$siteID) {
			return new WP_Error('rest_forbidden_context',
				__('Site is not connected to Wordfence Central.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}

		// verify signature.
		$data = $request->get_param('data');
		$dataChunks = explode('|', $data, 2);
		if (count($dataChunks) !== 2) {
			return new WP_Error('rest_forbidden_context',
				__('Data is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}
		if (!preg_match('/[0-9a-f]{64}/i', $dataChunks[0])) {
			return new WP_Error('rest_forbidden_context',
				__('Nonce format is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}
		if (!preg_match('/[0-9a-f\-]{36}/i', $dataChunks[1])) {
			return new WP_Error('rest_forbidden_context',
				__('Site ID is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}
		if (!hash_equals($siteID, $dataChunks[1])) {
			return new WP_Error('rest_forbidden_context',
				__('Site ID is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}

		$signature = $request->get_param('signature');
		$nonce1 = self::generateNonce();
		$nonce2 = self::generateNonce(-1);
		$verfiedNonce = hash_equals($nonce1, $dataChunks[0]) || hash_equals($nonce2, $dataChunks[0]);

		if (!$verfiedNonce) {
			return new WP_Error('rest_forbidden_context',
				__('Nonce is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}
		$signature = pack('H*', $signature);
		if (!ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $data, wfConfig::get('wordfenceCentralPK'))) {
			return new WP_Error('rest_forbidden_context',
				__('Signature is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}

		$response = rest_ensure_response(array(
			'token' => (string) self::generateToken(),
		));
		return $response;
	}
}