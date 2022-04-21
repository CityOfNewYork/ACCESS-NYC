<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE_Models_Language {
	public $code;
	public $name;

	/**
	 * @param string $code
	 * @param string $name
	 */
	public function __construct( $code = null, $name = null ) {
		$this->code = $code;
		$this->name = $name;
	}


}