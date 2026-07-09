<?php

abstract class wfRESTBaseController {

	protected $tokenData;

	/**
	 * @param WP_REST_Request $request
	 * @return WP_Error|bool
	 */
	public function verifyToken($request) {
		$validToken = $this->isTokenValid($request);

		if ($validToken &&
			!is_wp_error($validToken) &&
			$this->tokenData['body']['sub'] === wfConfig::get('wordfenceCentralSiteID')
		) {
			return true;
		}

		if (is_wp_error($validToken)) {
			return $validToken;
		}

		return new WP_Error('rest_forbidden_context',
			__('Token is invalid.', 'wordfence'),
			array('status' => rest_authorization_required_code()));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public function isTokenValid($request) {
		$authHeader = $request->get_header('Authorization');
		if (!$authHeader) {
			$authHeader = $request->get_header('X-Authorization');
		}
		if (stripos($authHeader, 'bearer ') !== 0) {
			return new WP_Error('rest_forbidden_context',
				__('Authorization header format is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}

		$token = trim(substr($authHeader, 7));
		$jwt = new wfJWT();

		try {
			$this->tokenData = $jwt->decode($token);

		} catch (wfJWTException $e) {
			return new WP_Error('rest_forbidden_context',
				$e->getMessage(),
				array('status' => rest_authorization_required_code()));

		} catch (Exception $e) {
			return new WP_Error('rest_forbidden_context',
				__('Token is invalid.', 'wordfence'),
				array('status' => rest_authorization_required_code()));
		}

		return true;
	}
}