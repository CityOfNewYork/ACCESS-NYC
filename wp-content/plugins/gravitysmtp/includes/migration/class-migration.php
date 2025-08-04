<?php

namespace Gravity_Forms\Gravity_SMTP\Migration;

class Migration {

	private $og_identifier;
	private $new_identifier;

	public function __construct( $og_identifier, $new_identifier ) {
		$this->og_identifier  = $og_identifier;
		$this->new_identifier = $new_identifier;
	}

	public function migrate() {
		$original_value = $this->get_original_value();
		$new_value      = $this->store_new_value( $original_value );

		return $new_value;
	}

	private function get_original_value() {
		if ( is_string( $this->og_identifier ) ) {
			return get_option( $this->og_identifier, null );
		}

		return call_user_func( $this->og_identifier );
	}

	private function store_new_value( $original_value ) {
		if ( is_string( $this->new_identifier ) ) {
			update_option( $this->new_identifier, $original_value );
		}

		call_user_func( $this->new_identifier, $original_value );
	}

}