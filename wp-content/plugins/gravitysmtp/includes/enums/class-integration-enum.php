<?php

namespace Gravity_Forms\Gravity_SMTP\Enums;

class Integration_Enum {

	/**
	 * Get the title for a given service key.
	 *
	 * @param string $service
	 *
	 * @return string
	 */
	public static function title( $service ) {
		$title = '';

		switch ( $service ) {
			case 'amazon-ses':
				$title = 'Amazon SES';
				break;
			case 'brevo':
				$title = 'Brevo';
				break;
			case 'generic':
				$title = 'Custom SMTP';
				break;
			case 'google':
				$title = 'Google';
				break;
			case 'mailgun':
				$title = 'Mailgun';
				break;
			case 'mandrill':
				$title = 'Mandrill';
				break;
			case 'microsoft':
				$title = 'Microsoft';
				break;
			case 'outlook':
				$title = 'Outlook';
				break;
			case 'postmark':
				$title = 'Postmark';
				break;
			case 'sendgrid':
				$title = 'SendGrid';
				break;
			case 'smtp2go':
				$title = 'SMTP2GO';
				break;
			case 'sparkpost':
				$title = 'Sparkpost';
				break;
			case 'zoho-mail':
				$title = 'Zoho';
				break;
			case 'wp_mail':
				$title = 'WordPress';
				break;
		}
		return $title;
	}

	/**
	 * Get the svg title for the grid cell of an integration.
	 *
	 * @param string $service
	 *
	 * @return string
	 */
	public static function svg_title( $service ) {
		/* translators: 1: the name of the integration (only used if $service does not equal 'wp_mail'). */
		return $service == 'wp_mail' ? esc_html__( 'The default WordPress mailer was used to send this email', 'gravitysmtp' ) : sprintf( esc_html__( 'The %1$s integration was used to send this email', 'gravitysmtp' ), self::title( $service ) );
	}

}
