<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Header_Parser {

	public $standard_headers = array(
		'from',
		'reply-to',
		'cc',
		'bcc',
		'content-type',
	);

	public function parse( $headers ) {
		if ( is_string( $headers ) ) {
			$headers = preg_split( "/\r\n|\n|\r/", $headers );
		}

		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		$parsed = array();

		foreach ( $headers as $key => $value ) {

			// Headers are numerically-indexed; get key/value from string content.
			if ( is_numeric( $key ) ) {
				$values = $this->get_header_from_string( $value );

				if ( ! $values ) {
					continue;
				}

				$parsed_key   = $values['key'];
				$parsed_value = $values['value'];
			} else {
				$parsed_key   = in_array( strtolower( $key ), $this->standard_headers ) ? strtolower( $key ) : $key;
				$parsed_value = $value;
			}

			if ( $parsed_key !== 'cc' && $parsed_key !== 'bcc' ) {
				$parsed[ $parsed_key ] = $parsed_value;
				continue;
			}

			if ( is_a( $parsed_value, Recipient_Collection::class ) ) {
				$parsed[ $parsed_key ] = $parsed_value;
				continue;
			}

			$parsed_value = $this->get_email_from_header( $key, $parsed_value );

			$parsed[ $parsed_key ] = $parsed_value;
		}

		return $parsed;
	}

	public function get_header_from_string( $string ) {
		$parts = explode( ':', $string );

		if ( count( $parts ) < 2 ) {
			return false;
		}

		$key = trim( $parts[0] );

		return array(
			'key'   => in_array( strtolower( $key ), $this->standard_headers ) ? strtolower( $key ) : $key,
			'value' => trim( $parts[1] ),
		);
	}

	public function get_email_from_header( $header_name, $header_string ) {
		if ( is_string( $header_string ) ) {
			$string = str_replace( sprintf( '%s:', $header_name ), '', $header_string );
		}

		if ( is_array( $header_string ) && isset( $header_string[0]['email'] ) ) {
			$string = $header_string[0]['email'];
		}

		$recipients = new Recipient_Collection();

		if ( $this->has_csv_emails( $string ) ) {
			$strings = $this->get_csv_emails( $string );

			foreach ( $strings as $string ) {
				$email_data = $this->get_email_from_header( $header_name, $string )->recipients();
				array_walk( $email_data, function( $email_item ) use ( $recipients ) {
					$recipients->add_raw( $email_item->email(), $email_item->name() );
				});
			}

			return $recipients;
		}

		if ( strpos( $string, '<' ) !== false ) {
			preg_match( '/([^<]*)<([^>]*)>/', $string, $email_data );

			$recipients->add_raw( $email_data[2], trim( $email_data[1], '" ' ) );
			return $recipients;
		}

		$recipients->add_raw( trim( $string ), '' );
		return $recipients;
	}

	public function has_csv_emails( $string ) {
		preg_match('/.+@.+,.+@.+/', $string, $matches);

		return ! empty( $matches );
	}

	public function get_csv_emails( $string ) {
		$parts  = explode( ',', $string );
		$stored = array();
		$emails = array();
		$length = count( $parts );

		foreach ( $parts as $key => $part ) {
			$stored[] = $part;

			if ( strpos( $part, '@' ) === false && $key < ( $length - 1 ) ) {
				continue;
			}

			$emails[] = trim( implode( ',', $stored ) );
			$stored   = array();
		}

		return $emails;
	}

	public function get_formatted_cc( $values ) {
		$response = array();

		foreach( $values as $item ) {
			$response[] = $item['email'];
		}

		return implode( ',', $response );
	}

}