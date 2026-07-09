<?php

require_once(dirname(__FILE__) . '/wfRESTBaseController.php');

class wfRESTScanController extends wfRESTBaseController {

	/**
	 * @todo Setup routes to modify scan results.
	 */
	public function registerRoutes() {
		register_rest_route('wordfence/v1', '/scan/issues', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array($this, 'getIssuesList'),
			'permission_callback' => array($this, 'verifyToken'),
			'group'               => array(
				'description' => __('Scan result group or all results.', 'wordfence'),
				'type'        => 'string',
				'required'    => false,
			),
			'offset'              => array(
				'description' => __('Offset of scan results to return.', 'wordfence'),
				'type'        => 'int',
				'required'    => false,
			),
			'limit'               => array(
				'description' => __('Number of scan results to return.', 'wordfence'),
				'type'        => 'int',
				'required'    => false,
			),
		));
		register_rest_route('wordfence/v1', '/scan', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'startScan'),
			'permission_callback' => array($this, 'verifyToken'),
		));
		register_rest_route('wordfence/v1', '/scan', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array($this, 'stopScan'),
			'permission_callback' => array($this, 'verifyToken'),
		));
		register_rest_route('wordfence/v1', '/scan/issue', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array($this, 'updateIssue'),
			'permission_callback' => array($this, 'verifyToken'),
		));
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function getIssuesList($request) {
		$group = $request['group'] ? $request['group'] : 'all';
		$offset = absint($request['offset']);
		$limit = absint($request['limit']);
		if ($limit === 0) {
			$limit = 100;
		}
		switch ($group) {
			case 'pending':
				$count = wfIssues::shared()->getPendingIssueCount();
				$issues = wfIssues::shared()->getPendingIssues($offset, $limit);
				break;

			default: // Return all issues.
				$count = wfIssues::shared()->getIssueCount();
				$issues = wfIssues::shared()->getIssues($offset, $limit);
				break;
		}

		$response = rest_ensure_response(array(
			'count'          => $count,
			'last-scan-time' => wfConfig::get('scanTime'),
			'issues'         => $issues,
		));
		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function startScan($request) {
		wordfence::status(1, 'info', sprintf(/* translators: Localized date. */ __('Wordfence scan starting at %s from Wordfence Central', 'wordfence'),
			date('l jS \of F Y h:i:s A', current_time('timestamp'))));

		try {
			wfScanEngine::startScan();

		} catch (wfScanEngineTestCallbackFailedException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
			wfUtils::clearScanLock();
			$response = rest_ensure_response(array(
				'success'    => false,
				'error-code' => $e->getCode(),
				'error'      => $e->getMessage(),
			));
			return $response;

		} catch (Exception $e) {
			if ($e->getCode() != wfScanEngine::SCAN_MANUALLY_KILLED) {
				wfConfig::set('lastScanCompleted', $e->getMessage());
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_GENERAL);

				$response = rest_ensure_response(array(
					'success'    => false,
					'error-code' => $e->getCode(),
					'error'      => $e->getMessage(),
				));
				return $response;
			}
		}

		$response = rest_ensure_response(array(
			'success' => true,
		));
		return $response;

	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function stopScan($request) {
		wordfence::status(1, 'info', __('Scan stop request received from Wordfence Central.', 'wordfence'));
		wordfence::status(10, 'info', __('SUM_KILLED:A request was received to stop the previous scan from Wordfence Central.', 'wordfence'));
		wfUtils::clearScanLock(); //Clear the lock now because there may not be a scan running to pick up the kill request and clear the lock
		wfScanEngine::requestKill();
		wfConfig::remove('scanStartAttempt');
		wfConfig::set('lastScanFailureType', false);
		$response = rest_ensure_response(array(
			'success' => true,
		));
		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return mixed|WP_REST_Response
	 */
	public function updateIssue($request) {
		$issue = $request['issue'];
		$id = is_array($issue) && array_key_exists('id', $issue) ? $issue['id'] : null;
		$status = is_array($issue) && array_key_exists('status', $issue) ? $issue['status'] : null;

		if ($id) {
			$wfdb = new wfDB();
			$wfdb->queryWrite("update " . wfDB::networkTable('wfIssues') . " set status='%s' where id=%d", $status, $id);
			$response = rest_ensure_response(array(
				'success' => true,
			));
			return $response;
		}
		$response = rest_ensure_response(array(
			'success'    => false,
			'error'      => 'Issue not found.',
		));
		return $response;

	}
}