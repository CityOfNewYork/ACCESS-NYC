<?php

namespace ACFML\Strings\Transformer;

use ACFML\Strings\Package;

class Register implements Transformer {

	/** @var Package $package */
	private $package;

	/**
	 * @param Package $package
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * @return void
	 */
	public function start() {
		$this->package->recordRegisteredStrings();
	}

	/**
	 * @return void
	 */
	public function end() {
		$this->package->cleanupUnusedStrings();
	}

	/**
	 * @param string $value
	 * @param array  $stringData
	 *
	 * @return string
	 */
	public function transform( $value, $stringData ) {
		$this->package->register( $value, $stringData );

		return $value;
	}
}
