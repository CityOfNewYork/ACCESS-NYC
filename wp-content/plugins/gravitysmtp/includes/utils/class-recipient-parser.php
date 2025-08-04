<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Recipient_Parser {

	public function parse( $to ) {
		// Protect against double-parsing.
		if ( is_a( $to, Recipient_Collection::class ) ) {
			return $to;
		}

		if ( is_array( $to ) && isset( $to['email'] ) ) {
			$to = array( $to );
		}

		if ( is_string( $to ) ) {
			$to = array_filter( explode( ',', $to ) );
		}

		$collection = new Recipient_Collection();

		if ( ! is_array( $to ) ) {
			$to = array();
		}

		array_walk( $to, function ( $recipient ) use ( $collection ) {
			if ( is_string( $recipient ) ) {
				$collection->add( $this->get_recipient_from_string( $recipient ) );
			}

			if ( isset( $recipient['email'] ) ) {
				$name = isset( $recipient['name'] ) ? $recipient['name'] : '';
				$collection->add( new Recipient( $recipient['email'], $name ) );
			}
		} );

		return $collection;
	}

	public function get_email_counts( $extra ) {
		$values = array(
			'to'  => isset( $extra['to'] ) ? $extra['to'] : '',
			'cc'  => isset( $extra['headers']['cc'] ) ? $extra['headers']['cc'] : '',
			'bcc' => isset( $extra['headers']['bcc'] ) ? $extra['headers']['bcc'] : ''
		);

		$count = 0;

		foreach ( $values as $recipients ) {
			$recipients = $this->parse( $recipients );
			$count      += $recipients->count();
		}

		return $count;
	}

	private function get_recipient_from_string( $string ) {
		if ( strpos( $string, '<' ) === false ) {
			return new Recipient( trim( $string ), '' );
		}

		// Email seems to be in RFC5322 Mailbox format
		preg_match( '/([^<]*)<([^>]*)>/', $string, $email_data );

		return new Recipient( $email_data[2], trim( $email_data[1], '" ' ) );
	}
}