<?php

namespace WPML\Action;

/**
 * Class Type
 * @package WPML\Action
 *
 * Determines the type of action that a class implements. Can be
 * one or more of:
 * backend, frontend, ajax, rest, cli or dic
 *
 * dic means that the class can be loaded via Dependency Injection Container
 */

class Type {

	private $backend_actions = [ 'IWPML_Backend_Action_Loader', 'IWPML_Backend_Action' ];
	private $frontend_actions = [ 'IWPML_Frontend_Action_Loader', 'IWPML_Frontend_Action' ];
	private $ajax_actions = [ 'IWPML_AJAX_Action_Loader', 'IWPML_AJAX_Action' ];
	private $rest_actions = [ 'IWPML_REST_Action_Loader', 'IWPML_REST_Action' ];
	private $cli_actions = [ 'IWPML_CLI_Action_Loader', 'IWPML_CLI_Action' ];
	private $dic_actions = [ 'IWPML_DIC_Action' ];

	/** @var array */
	private $implementations;

	/**
	 * Info constructor.
	 *
	 * @param string $class_name The class name of the action or action loader
	 */
	public function __construct( $class_name ) {
		$this->implementations = class_implements( $class_name );
	}

	/**
	 * @param $type The type of action 'backend', 'frontend', 'ajax', 'rest', 'cli' or 'dic'
	 *
	 * @return bool
	 */
	public function is( $type ) {
		$action_type = $type . '_actions';
		return $this->has_implementation( $this->$action_type );
	}

	/**
	 * @param array $interfaces
	 *
	 * @return bool
	 */
	private function has_implementation( $interfaces ) {
		return count( array_intersect( $this->implementations, $interfaces ) ) > 0;
	}

}