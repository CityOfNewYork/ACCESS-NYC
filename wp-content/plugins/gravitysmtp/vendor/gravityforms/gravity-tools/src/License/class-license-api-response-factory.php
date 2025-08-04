<?php

namespace Gravity_Forms\Gravity_Tools\License;

use Gravity_Forms\Gravity_Tools\License\License_API_Response;
use Gravity_Forms\Gravity_Tools\Utils\Common;

/**
 * Class License_API_Response_Factory
 *
 * Concrete response factory used to return a License API Response
 *
 * @since 1.0
 *
 * @package Gravity_Forms\Gravity_Tools\License
 */
class License_API_Response_Factory {

	private $transient_strategy;

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * License_API_Response_Factory constructor
	 *
	 * @since 1.0
	 *
	 * @param $transient_strategy
	 */
	public function __construct( $transient_strategy, $common ) {
		$this->transient_strategy = $transient_strategy;
		$this->common = $common;
	}

	/**
	 * Create a new License API Response from the given data.
	 *
	 * @since 1.0
	 *
	 * @param mixed ...$args
	 *
	 * @return GF_License_API_Response
	 */
	public function create( ...$args ) {
		$data     = $args[0];
		$validate = isset( $args[1] ) ? $args[1] : true;

		return new License_API_Response( $data, $validate, $this->transient_strategy, $this->common );
	}

}