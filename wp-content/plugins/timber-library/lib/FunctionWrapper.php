<?php

namespace Timber;

use Timber\Helper;

class FunctionWrapper {

	private $_class;
	private $_function;
	private $_args;
	private $_use_ob;

	public function __toString() {
		 try {
			return (string) $this->call();
		 } catch ( \Exception $e ) {
		 	return 'Caught exception: '.$e->getMessage()."\n";
		 }
	}

	/**
	 *
	 *
	 * @param callable $function
	 * @param array   $args
	 * @param bool    $return_output_buffer
	 */
	public function __construct( $function, $args = array(), $return_output_buffer = false ) {
		if ( is_array($function) ) {
			if ( (is_string($function[0]) && class_exists($function[0])) || gettype($function[0]) === 'object' ) {
				$this->_class = $function[0];
			}
			
			if ( is_string($function[1]) ) {
				$this->_function = $function[1];
			}
		} else {
			$this->_function = $function;
		}

		$this->_args = $args;
		$this->_use_ob = $return_output_buffer;

		add_filter('timber/twig', array(&$this, 'add_to_twig'));
	}

	/**
	 *
	 *
	 * @param Twig_Environment $twig
	 * @return Twig_Environment
	 */
	public function add_to_twig( $twig ) {
		$wrapper = $this;

		$twig->addFunction(new \Twig_SimpleFunction($this->_function, function() use ($wrapper) {
					return call_user_func_array(array($wrapper, 'call'), func_get_args());
				} ));

		return $twig;
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function call() {
		$args = $this->_parse_args(func_get_args(), $this->_args);
		$callable = (isset($this->_class)) ? array($this->_class, $this->_function) : $this->_function;

		if ( $this->_use_ob ) {
			return Helper::ob_function($callable, $args);
		} else {
			return call_user_func_array($callable, $args);
		}
	}

	/**
	 *
	 *
	 * @param array   $args
	 * @param array   $defaults
	 * @return array
	 */
	private function _parse_args( $args, $defaults ) {
		$_arg = reset($defaults);

		foreach ( $args as $index => $arg ) {
			$defaults[$index] = is_null($arg) ? $_arg : $arg;
			$_arg = next($defaults);
		}

		return $defaults;
	}

}
