<?php
/*
	Plugin Name: DroolsProxy
	Description: Backend Proxy for Drools web requests
	Author:      Blue State Digital
*/

namespace Drools;

if (!defined('WPINC')) {
	die; //no direct access
}
require plugin_dir_path( __FILE__ ) . 'settings.php';

add_action( 'wp_ajax_drools', '\Drools\incoming' );
add_action( 'wp_ajax_nopriv_drools', '\Drools\incoming' );

function incoming() {
	$url = get_option('drools_url');
	$user = get_option('drools_user');
	$pass = get_option('drools_pass');

	$url = (!empty($url)) ? $url : getenv('DROOLS_URL');
	$user = (!empty($user)) ? $user : getenv('DROOLS_USER');
	$pass = (!empty($pass)) ? $pass : getenv('DROOLS_PASS');

	if (empty($url) || empty($user) || empty($pass)) {
		wp_send_json([
			'status' => 'fail',
			'message' => 'invalid configuration'
		], 412);
		wp_die();
	}

	$uid = uniqid();
	do_action( 'peu_data', $_POST['staff'], $_POST['client'], $uid );
	do_action( 'drools_request', $_POST['data'], $uid );

	$response = request($url, json_encode($_POST['data']), $user, $pass);

	if ( $response === false || empty($response) ) {
		wp_send_json([ 'status'=>'fail'], 500 );
		wp_die();
	}

	$ret = json_decode( $response );

	do_action( 'drools_response', $ret, $uid );

	$ret->GUID = $uid;
	wp_send_json( $ret, 200 );
	wp_die();
}

function request($url, $data, $user, $pass) {

	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 3,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_POST=>true,
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_USERPWD => $user.":".$pass,
		CURLOPT_HTTPHEADER => [
			"Content-Type: application/json",
			"X-KIE-ContentType: json",
			"Content-Length: " . strlen($data),
		]

	]);

	$response = curl_exec($ch);
	curl_close($ch);

	return $response;
}