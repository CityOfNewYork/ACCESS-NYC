<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Recipient {

	public $email;
	public $name;

	public function __construct( $email, $name ) {
		$this->email = $email;
		$this->name  = $name;
	}

	public function name() {
		return $this->name;
	}

	public function email() {
		return $this->email;
	}

	public function mailbox() {
		if ( empty( $this->name ) ) {
			return $this->email;
		}

		return sprintf( '%s <%s>', $this->name, $this->email );
	}

	public function as_array() {
		return array_filter ( array(
			'email' => $this->email,
			'name'  => $this->name,
		) );
	}
}