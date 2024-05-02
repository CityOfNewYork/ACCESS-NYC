<?php

namespace ACFML\Strings\Transformer;

use ACFML\Strings\Package;

class Translate implements Transformer {

	/** @var Package $package */
	private $package;

	/**
	 * @param Package $package
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * @param string $value
	 * @param array  $stringData
	 *
	 * @return string
	 */
	public function transform( $value, $stringData ) {
		// phpcs:ignore WordPress.WP.I18n
		return $this->package->translate( $value, $stringData );
	}
}
