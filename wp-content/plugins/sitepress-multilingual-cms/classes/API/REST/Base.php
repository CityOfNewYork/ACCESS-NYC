<?php

namespace WPML\Rest;

use IWPML_Action;

abstract class Base implements ITarget, IWPML_Action {

	/** @var Adaptor */
	private $adaptor;

	public function __construct( Adaptor $adaptor ) {
		$this->adaptor = $adaptor;
		$adaptor->set_target( $this );
	}

	/**
	 * @return string
	 */
	abstract public function get_namespace();

	public function add_hooks() {
		$this->adaptor->add_hooks();
	}

	/**
	 * @return array
	 */
	public static function getStringType() {
		return [
			'type'              => 'string',
			'sanitize_callback' => 'WPML_REST_Arguments_Sanitation::string',
		];
	}

	/**
	 * @return array
	 */
	public static function getIntType() {
		return [
			'type'              => 'int',
			'validate_callback' => 'WPML_REST_Arguments_Validation::integer',
			'sanitize_callback' => 'WPML_REST_Arguments_Sanitation::integer',
		];
	}
}
