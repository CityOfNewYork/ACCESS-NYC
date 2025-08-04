<?php

use Gravity_Forms\Gravity_SMTP\Handler\Handler_Service_Provider;

if ( ! function_exists( 'get_option' ) ) {
	return;
}

$is_configured = \Gravity_Forms\Gravity_SMTP\Handler\Mail_Handler::is_minimally_configured();
$test_mode     = \Gravity_Forms\Gravity_SMTP\Handler\Mail_Handler::is_test_mode();

if ( ! function_exists( 'wp_mail' ) && $is_configured ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		/**
		 * Perform an action before sending an email.
		 *
		 * @param array $atts the attributes sent to wp_mail for this request
		 */
		do_action( 'gravitysmtp_before_email_send', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

		if ( \gsmtp_fi_third_party_needs_init() ) {
			\Gravity_Forms\Gravity_SMTP\Gravity_SMTP::load_plugin();
		}

		return \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::container()->get( \Gravity_Forms\Gravity_SMTP\Handler\Handler_Service_Provider::HANDLER )->mail( $to, $subject, $message, $headers, $attachments );
	}
}

if ( ! function_exists( 'wp_mail' ) && ! $is_configured && $test_mode ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$test_mode = true;
		$atts      = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments', 'test_mode' ) );

		return true;
	}
}

if ( ! function_exists( 'gsmtp_fi_third_party_needs_init' ) ) {
	function gsmtp_fi_third_party_needs_init() {
		$container = \Gravity_Forms\Gravity_SMTP\Gravity_SMTP::container();
		$handler   = $container->get( Handler_Service_Provider::HANDLER );

		if ( ! is_null( $handler ) ) {
			return false;
		}

		$wfFunc = filter_input( INPUT_GET, '_wfsf' );

		// Wordfence check.
		if ( $wfFunc == 'unlockEmail' ) {
			return true;
		}

		$fdsusSignupNonce = filter_input( INPUT_POST, 'signup_nonce' );

		// Sign-up Sheets check.
		if ( ! empty( $fdsusSignupNonce ) && wp_verify_nonce( $fdsusSignupNonce, 'fdsus_signup_submit' ) ) {
			return true;
		}

		return false;
	}
}
