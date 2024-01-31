<?php

namespace WPML\ST\StringsFilter;

class StringEntity {
	/** @var string|bool */
	private $value;

	/** @var string */
	private $name;

	/** @var string */
	private $domain;

	/** @var string */
	private $context;

	/**
	 * @param string|bool $value
	 * @param string      $name
	 * @param string      $domain
	 * @param string      $context
	 */
	public function __construct( $value, $name, $domain, $context = '' ) {
		$this->value   = $value;
		$this->name    = $name;
		$this->domain  = $domain;
		$this->context = $context;
	}

	/**
	 * @return string|bool
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @param array $data
	 *
	 * @return StringEntity
	 */
	public static function fromArray( array $data ) {
		return new self( $data['value'], $data['name'], $data['domain'], $data['context'] );
	}
}
