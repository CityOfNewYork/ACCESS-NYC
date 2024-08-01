<?php

namespace WordfenceLS;

class Utility_MeasuredString {

	public $string;
	public $length;

	public function __construct($string) {
		$this->string = $string;
		$this->length = strlen($string);
	}

	public function __toString() {
		return $this->string;
	}

}