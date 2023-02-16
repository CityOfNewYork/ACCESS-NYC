<?php
$constructor = '';
$isJs        = false;

$factory           = new WPML_TM_AMS_ATE_Console_Section_Factory();
$ateConsoleSection = $factory->create();

$response = wp_remote_request( $ateConsoleSection->getWidgetScriptUrl(), [ 'timeout' => 20 ] );

$errors = [];
if ( is_wp_error( $response ) ) {
	$errors[] = 'WP_Error response';
	$errors[] = $response->get_error_message();
} else {
	$headerData = wp_remote_retrieve_headers( $response )->getAll();
	if ( ! $headerData ) {
		$errors[] = 'Empty headers when retrieving the ATE Widget App';
	} else {
		$isJs = $headerData && strpos( $headerData['content-type'], 'javascript' );
	}

	header( $_SERVER['SERVER_PROTOCOL'] . ' ' . $response['response']['code'] . ' ' . $response['response']['message'] );
	header( 'content-type: ' . $headerData['content-type'] );

	$app = wp_remote_retrieve_body( $response );

	$constructor = wp_json_encode( $ateConsoleSection->get_widget_constructor() );
	if ( ! $app || ! trim( $app ) ) {
		$errors[] = 'Empty response when retrieving the ATE Widget App';
	}
}

if ( WP_DEBUG ) {
	if ( count( $errors ) > 0 ) {
		$errors[] = ':: URL:' . PHP_EOL . PHP_EOL . $ateConsoleSection->getWidgetScriptUrl();
		if ( is_wp_error( $response ) ) {
			$errors[] = ':: Error:' . PHP_EOL . PHP_EOL . var_export( $response, true );
		} else {
			$errors[] = ':: Response:' . PHP_EOL . PHP_EOL . var_export( $response['response'], true );
		}
	}

	if ( $errors ) {

		if ( $isJs ) {
			echo '/** ' . PHP_EOL;
		}

		echo join( PHP_EOL . PHP_EOL, $errors );

		if ( $isJs ) {
			echo '*/' . PHP_EOL;
		}
	}

}

if ( ! $errors ) {
	echo <<<WIDGET_CONSTRUCTOR
$app
LoadEateWidget($constructor);
	
WIDGET_CONSTRUCTOR;
}