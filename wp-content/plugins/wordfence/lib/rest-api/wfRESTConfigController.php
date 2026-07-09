<?php

use WordfenceLS\Controller_Settings;

require_once(dirname(__FILE__) . '/wfRESTBaseController.php');

class wfRESTConfigController extends wfRESTBaseController {
	const WF_CENTRAL_USER_MARKER = '_wf_central_user_'; //Marker used to indicate that the site was disconnected by a user associated with the site's Central account
	const WF_CENTRAL_FAILURE_MARKER = '_wf_central_failure_'; //Marker used to indicate that the site was disconnected due to exceeding the error threshold

	public static function disconnectConfig($adminEmail = null) {
		global $wpdb;
		delete_transient('wordfenceCentralJWT' . wfConfig::get('wordfenceCentralSiteID'));

		if (is_null($adminEmail)) {
			$user = wp_get_current_user();
			if ($user && $user->exists()) {
				$adminEmail = $user->user_email;
			}
			
			if (is_null($adminEmail)) {
				$adminEmail = wfConfig::get('wordfenceCentralConnectEmail');
			}
		}

		$result = $wpdb->query('DELETE FROM ' . wfDB::networkTable('wfConfig') . " WHERE name LIKE 'wordfenceCentral%'");

		wfConfig::set('wordfenceCentralDisconnected', true);
		wfConfig::set('wordfenceCentralDisconnectTime', time());
		wfConfig::set('wordfenceCentralDisconnectEmail', $adminEmail);
		wfConfig::set('wordfenceCentralConfigurationIssue', false);

		return !!$result;
	}

	public function registerRoutes() {
		register_rest_route('wordfence/v1', '/config', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'getConfig'),
			'permission_callback' => array($this, 'verifyToken'),
			'fields'              => array(
				'description' => __('Specific config options to return.', 'wordfence'),
				'type'        => 'array',
				'required'    => false,
			),
		));
		register_rest_route('wordfence/v1', '/config', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'setConfig'),
			'permission_callback' => array($this, 'verifyToken'),
			'fields'              => array(
				'description' => __('Specific config options to set.', 'wordfence'),
				'type'        => 'array',
				'required'    => true,
			),
		));
		register_rest_route('wordfence/v1', '/disconnect', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'disconnect'),
			'permission_callback' => array($this, 'verifyToken'),
		));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function getConfig($request) {
		$fields = (array) $request['fields'];

		$config = array();

		$firewall = new wfFirewall();
		$wafFields = array(
			'autoPrepend'                    => $firewall->protectionMode() === wfFirewall::PROTECTION_MODE_EXTENDED,
			'avoid_php_input'                => wfWAF::getInstance()->getStorageEngine()->getConfig('avoid_php_input', false) ? 1 : 0,
			'disabledRules'                  => array_keys((array) wfWAF::getInstance()->getStorageEngine()->getConfig('disabledRules')),
			'ruleCount'                      => count((array) wfWAF::getInstance()->getRules()),
			'disableWAFBlacklistBlocking'    => wfWAF::getInstance()->getStorageEngine()->getConfig('disableWAFBlacklistBlocking'),
			'enabled'                        => $firewall->wafStatus() !== wfFirewall::FIREWALL_MODE_DISABLED,
            'firewallMode'                   => $firewall->firewallMode(),
			'learningModeGracePeriod'        => wfWAF::getInstance()->getStorageEngine()->getConfig('learningModeGracePeriod'),
			'learningModeGracePeriodEnabled' => wfWAF::getInstance()->getStorageEngine()->getConfig('learningModeGracePeriodEnabled'),
			'subdirectoryInstall'            => $firewall->isSubDirectoryInstallation(),
			'wafStatus'                      => $firewall->wafStatus(),
		);
		$lsFields = array(
			Controller_Settings::OPTION_XMLRPC_ENABLED                   => Controller_Settings::shared()->get(Controller_Settings::OPTION_XMLRPC_ENABLED),
			Controller_Settings::OPTION_2FA_WHITELISTED                  => Controller_Settings::shared()->get(Controller_Settings::OPTION_2FA_WHITELISTED),
			Controller_Settings::OPTION_IP_SOURCE                        => Controller_Settings::shared()->get(Controller_Settings::OPTION_IP_SOURCE),
			Controller_Settings::OPTION_IP_TRUSTED_PROXIES               => Controller_Settings::shared()->get(Controller_Settings::OPTION_IP_TRUSTED_PROXIES),
			Controller_Settings::OPTION_REQUIRE_2FA_ADMIN                => Controller_Settings::shared()->get(Controller_Settings::OPTION_REQUIRE_2FA_ADMIN),
			Controller_Settings::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED => Controller_Settings::shared()->get(Controller_Settings::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED),
			Controller_Settings::OPTION_GLOBAL_NOTICES                   => Controller_Settings::shared()->get(Controller_Settings::OPTION_GLOBAL_NOTICES),
			Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED          => Controller_Settings::shared()->get(Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED),
			Controller_Settings::OPTION_REMEMBER_DEVICE_DURATION         => Controller_Settings::shared()->get(Controller_Settings::OPTION_REMEMBER_DEVICE_DURATION),
			Controller_Settings::OPTION_ALLOW_XML_RPC                    => Controller_Settings::shared()->get(Controller_Settings::OPTION_ALLOW_XML_RPC),
			Controller_Settings::OPTION_ENABLE_AUTH_CAPTCHA              => Controller_Settings::shared()->get(Controller_Settings::OPTION_ENABLE_AUTH_CAPTCHA),
			Controller_Settings::OPTION_RECAPTCHA_THRESHOLD              => Controller_Settings::shared()->get(Controller_Settings::OPTION_RECAPTCHA_THRESHOLD),
			Controller_Settings::OPTION_LAST_SECRET_REFRESH              => Controller_Settings::shared()->get(Controller_Settings::OPTION_LAST_SECRET_REFRESH),
		);
		// Convert the database strings to typed values.
		foreach ($lsFields as $lsField => $value) {
			$lsFields[$lsField] = Controller_Settings::shared()->clean($lsField, $value);
		}

		if (!$fields) {
			foreach (wfConfig::$defaultConfig as $group => $groupOptions) {
				foreach ($groupOptions as $field => $values) {
					$fields[] = $field;
				}
			}
			foreach ($wafFields as $wafField => $value) {
				$fields[] = 'waf.' . $wafField;
			}
			foreach ($lsFields as $lsField => $value) {
				$fields[] = 'wfls_settings_' . $lsField;
			}
		}

		foreach ($fields as $field) {
			if (strpos($field, 'waf.') === 0) {
				$wafField = substr($field, 4);
				if (array_key_exists($wafField, $wafFields)) {
					$config['waf'][$wafField] = $wafFields[$wafField];
				}
				continue;
			}

			if (strpos($field, 'wfls_settings_') === 0) {
				$lsField = substr($field, 14);
				if (array_key_exists($lsField, $lsFields)) {
					$config['wfls_settings_' . $lsField] = $lsFields[$lsField];
				}
				continue;
			}

			if (array_key_exists($field, wfConfig::$defaultConfig['checkboxes'])) {
				$config[$field] = (bool) wfConfig::get($field);

			} else if (array_key_exists($field, wfConfig::$defaultConfig['otherParams']) ||
				array_key_exists($field, wfConfig::$defaultConfig['defaultsOnly'])) {

				$configConfig = !empty(wfConfig::$defaultConfig['otherParams'][$field]) ?
					wfConfig::$defaultConfig['otherParams'][$field] : wfConfig::$defaultConfig['defaultsOnly'][$field];

				if (!empty($configConfig['validation']['type'])) {
					switch ($configConfig['validation']['type']) {
						case wfConfig::TYPE_INT:
							$config[$field] = wfConfig::getInt($field);
							break;

						case wfConfig::TYPE_DOUBLE:
						case wfConfig::TYPE_FLOAT:
							$config[$field] = floatval(wfConfig::get($field));
							break;

						case wfConfig::TYPE_BOOL:
							$config[$field] = (bool) wfConfig::get($field);
							break;

						case wfConfig::TYPE_ARRAY:
							$config[$field] = wfConfig::get_ser($field);
							break;

						case wfConfig::TYPE_STRING:
						default:
							$config[$field] = wfConfig::get($field);
							break;
					}
				} else {
					$config[$field] = wfConfig::get($field);
				}

			} else if (in_array($field, wfConfig::$serializedOptions)) {
				$config[$field] = wfConfig::get_ser($field);
			}
		}

		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		parse_str($api->makeAPIQueryString(), $qs);
		$systemInfo = json_decode(wfUtils::base64url_decode($qs['s']), true);
		$systemInfo['output_buffering'] = ini_get('output_buffering');
		$systemInfo['ip'] = wfUtils::getIPAndServerVariable();
		$systemInfo['detected_ips'] = wfUtils::getAllServerVariableIPs();
		$systemInfo['admin_url'] = network_admin_url();

		$response = rest_ensure_response(array(
			'config' => $config,
			'info'   => $systemInfo,
		));
		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function setConfig($request) {
		wfCentral::preventConfigurationSync();

		$fields = $request['fields'];
		if (is_array($fields) && $fields) {
			$loginSecurityConfig = array();
			foreach ($fields as $key => $value) {
				if (strpos($key, 'wfls_settings_') === 0) {
					$lsField = substr($key, 14);
					$loginSecurityConfig[$lsField] = $value;
				}
			}

			if ($loginSecurityConfig) {
				$errors = Controller_Settings::shared()->validate_multiple($loginSecurityConfig);

				if ($errors !== true) {
					if (count($errors) == 1) {
						return new WP_Error('rest_set_config_error',
							sprintf(
								/* translators: Error message. */
								__('An error occurred while saving the configuration: %s', 'wordfence'), $errors[0]['error']),
							array('status' => 422));

					} else if (count($errors) > 1) {
						$compoundMessage = array();
						foreach ($errors as $e) {
							$compoundMessage[] = $e['error'];
						}
						return new WP_Error('rest_set_config_error',
							sprintf(
								/* translators: Error message. */
								__('Errors occurred while saving the configuration: %s', 'wordfence'), implode(', ', $compoundMessage)),
							array('status' => 422));
					}

					return new WP_Error('rest_set_config_error',
						__('Errors occurred while saving the configuration.', 'wordfence'),
						array('status' => 422));
				}

				try {
					Controller_Settings::shared()->set_multiple($loginSecurityConfig);
					foreach ($fields as $key => $value) {
						if (strpos($key, 'wfls_settings_') === 0) {
							unset($fields[$key]);
						}
					}

				} catch (Exception $e) {
					return new WP_Error('rest_save_config_error',
						sprintf(
						/* translators: Error message. */
							__('A server error occurred while saving the configuration: %s', 'wordfence'), $e->getMessage()),
						array('status' => 500));
				}
			}

			$errors = wfConfig::validate($fields);
			if ($errors !== true) {
				if (count($errors) == 1) {
					return new WP_Error('rest_set_config_error',
						sprintf(
						/* translators: Error message. */
							__('An error occurred while saving the configuration: %s', 'wordfence'), $errors[0]['error']),
						array('status' => 422));

				} else if (count($errors) > 1) {
					$compoundMessage = array();
					foreach ($errors as $e) {
						$compoundMessage[] = $e['error'];
					}
					return new WP_Error('rest_set_config_error',
						sprintf(
						/* translators: Error message. */
							__('Errors occurred while saving the configuration: %s', 'wordfence'), implode(', ', $compoundMessage)),
						array('status' => 422));
				}

				return new WP_Error('rest_set_config_error',
					__('Errors occurred while saving the configuration.', 'wordfence'),
					array('status' => 422));
			}

			try {
				wfConfig::save($fields);
				return rest_ensure_response(array(
					'success' => true,
				));

			} catch (Exception $e) {
				return new WP_Error('rest_save_config_error',
					sprintf(
					/* translators: Error message. */
						__('A server error occurred while saving the configuration: %s', 'wordfence'), $e->getMessage()),
					array('status' => 500));
			}
		}
		return new WP_Error('rest_save_config_error',
			__("Validation error: 'fields' parameter is empty or not an array.", 'wordfence'),
			array('status' => 422));

	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function disconnect($request) {
		self::disconnectConfig(!empty($this->tokenData['adminEmail']) ? $this->tokenData['adminEmail'] : self::WF_CENTRAL_USER_MARKER);
		return rest_ensure_response(array(
			'success' => true,
		));
	}
}