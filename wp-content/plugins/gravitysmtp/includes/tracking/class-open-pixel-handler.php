<?php

namespace Gravity_Forms\Gravity_SMTP\Tracking;

use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Basic_Encrypted_Hash;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Collection;

class Open_Pixel_Handler {

	const REWRITE_PARAM = 'gravitysmtp_open_tracking';

	/**
	 * @var Basic_Encrypted_Hash $encrypter
	 */
	private $encrypter;

	/**
	 * @var Event_Model
	 */
	private $events;

	public function __construct( $encrypter, $events ) {
		$this->encrypter = $encrypter;
		$this->events    = $events;
	}

	public function add_pixel( $email_id, $message, $attributes ) {
		$to           = $attributes['to'];

		if ( is_a( $to, Recipient_Collection::class ) ) {
			/**
			 * @var Recipient_Collection $to
			 */
			$to = $to->first()->email();
		}

		$hash          = $this->encrypter->encrypt( sprintf( '%s:%s:%s', $email_id, $to, $this->generate_random_string() ) );
		$url_safe_hash = strtr( rtrim( $hash, '=' ), '+/', '-_' ); // Replace + with -, / with _, and remove padding =
		$callback_url  = $this->get_callback_url( $url_safe_hash );
		$image         = sprintf( '<img src="%s" />', $callback_url );

		$message = str_replace( '</body>', $image . '</body>', $message );

		return $message;
	}

	private function rewrites_enabled() {
		return get_option('permalink_structure');
	}

	public function add_rewrite_rules() {
		if ( ! $this->rewrites_enabled() ) {
			return;
		}

		add_rewrite_rule( 'tracking/open/([a-zA-Z0-9-%]+)[/]?$', sprintf( 'index.php?%s=$matches[1]', self::REWRITE_PARAM ), 'top' );

		add_filter( 'query_vars', function ( $query_vars ) {
			$query_vars[] = self::REWRITE_PARAM;

			return $query_vars;
		} );
	}

	public function handle_redirect() {
		header( 'Content-Type: image/png' );

		$message = get_query_var( self::REWRITE_PARAM );
		$message = urldecode( $message );

		if ( $message == false || $message == '' ) {
			$this->send_response();
		}

		$encoded_hash = strtr( $message, '-_', '+/' ); // Replace - with +, _ with /
		$decrypted    = $this->encrypter->decrypt( $encoded_hash );
		$parts        = explode( ':', $decrypted );

		if ( count( $parts ) !== 3 ) {
			$this->send_response();
		}

		$email_id = $parts[0];
		$to       = $parts[1];

		$this->events->set_opened( $email_id, $to, 1 );

		$this->send_response();
	}

	private function send_response() {
		echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
		die();
	}

	private function get_callback_url( $hash ) {
		if ( ! $this->rewrites_enabled() ) {
			return add_query_arg( self::REWRITE_PARAM, urlencode( $hash ), site_url() );
		}

		return site_url( 'tracking/open/' . urlencode( $hash ) );
	}

	private function generate_random_string() {
		$length = rand( 1, 12 );

		return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
	}

}