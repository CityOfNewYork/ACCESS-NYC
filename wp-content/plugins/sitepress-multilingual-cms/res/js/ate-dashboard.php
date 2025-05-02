<?php
$constructor = '';
$isJs        = false;

$factory = new WPML_TM_AMS_ATE_Console_Section_Factory();

/** @var \WPML_TM_AMS_ATE_Console_Section|null $ateConsoleSection */
$ateConsoleSection = $factory->create();

if ( ! $ateConsoleSection ) {
	return;
}

$script_url = $ateConsoleSection->getDashboardScriptUrl();
$response = wp_remote_request($script_url, ['timeout' => 20]);

$errors = [];
if (is_wp_error($response)) {
	$errors[] = 'WP_Error response';
	$errors[] = $response->get_error_message();
} else {
	$headerData = wp_remote_retrieve_headers($response)->getAll();
	$status_code = wp_remote_retrieve_response_code($response);
	if ($status_code === 404) {
		$errors[] = 'ATE Dashboard script doesn\'t exist';
	}

	header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response['response']['code'] . ' ' . $response['response']['message']);
	header('content-type: ' . $headerData['content-type']);

	$app = wp_remote_retrieve_body($response);

	$constructor = wp_json_encode($ateConsoleSection->get_ams_constructor());
	if (! $app || ! trim($app)) {
		$errors[] = 'Empty response when retrieving the ATE DAshboard App';
	}
}

if (WP_DEBUG) {
	if (count($errors) > 0) {
		$errors[] = ':: URL:' . PHP_EOL . PHP_EOL . $script_url;
		if (is_wp_error($response)) {
			$errors[] = ':: Error:' . PHP_EOL . PHP_EOL . var_export($response, true);
		} else {
			$errors[] = ':: Response:' . PHP_EOL . PHP_EOL . var_export($response['response'], true);
		}
	}

	if ($errors) {
		echo '/** ' . PHP_EOL;
		echo join(PHP_EOL . PHP_EOL, $errors);
		echo '*/' . PHP_EOL;
	}
}

if (! $errors) {
	echo <<<DASHBOARD_CONSTRUCTOR
$app

var params = $constructor;

ateDashboard(params);

DASHBOARD_CONSTRUCTOR;
}
