<?php

namespace WPML\Utils;

/**
 * Class DebugBackTrace
 *
 * @package WPML\Utils
 */
class DebugBackTrace {

	/** @var array */
	private $debug_backtrace = [];

	/** @var int */
	private $limit;

	/** @var bool */
	private $provide_object;

	/** @var bool */
	private $ignore_args;

	/** @var string */
	private $debug_backtrace_function;

	/**
	 * DebugBackTrace constructor.
	 *
	 * @param int  $limit
	 * @param bool $provide_object
	 * @param bool $ignore_args
	 * @param null $debug_backtrace_function
	 */
	public function __construct(
		$limit = 0,
		$provide_object = false,
		$ignore_args = true,
		$debug_backtrace_function = null
	) {
		if ( ! $debug_backtrace_function ) {
			$debug_backtrace_function = 'debug_backtrace';
		}
		$this->limit                    = $limit;
		$this->provide_object           = $provide_object;
		$this->ignore_args              = $ignore_args;
		$this->debug_backtrace_function = $debug_backtrace_function;
	}

	/**
	 * @param array $functions
	 * @param bool  $refresh
	 *
	 * @return bool
	 */
	public function are_functions_in_call_stack( array $functions, $refresh = true ) {
		if ( empty( $this->debug_backtrace ) || $refresh ) {
			$this->get_backtrace();
		}

		$found = false;
		foreach ( $this->debug_backtrace as $frame ) {
			if ( isset( $frame['class'] ) ) {
				$frame_function = [ $frame['class'], $frame['function'] ];
			} else {
				$frame_function = $frame['function'];
			}
			if ( in_array( $frame_function, $functions, true ) ) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	/**
	 * @param string $function_name
	 * @param bool   $refresh
	 *
	 * @return bool
	 */
	public function is_function_in_call_stack( $function_name, $refresh = true ) {
		return $this->are_functions_in_call_stack( [ $function_name ], $refresh );
	}

	/**
	 * @param string $function_name
	 * @param bool   $refresh
	 *
	 * @return int
	 */
	public function count_function_in_call_stack( $function_name, $refresh = true ) {
		if ( empty( $this->debug_backtrace ) || $refresh ) {
			$this->get_backtrace();
		}

		$count = 0;
		foreach ( $this->debug_backtrace as $frame ) {
			if ( ! isset( $frame['class'] ) && $frame['function'] === $function_name ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * @param string $class_name
	 * @param string $function_name
	 * @param bool   $refresh
	 *
	 * @return bool
	 */
	public function is_class_function_in_call_stack( $class_name, $function_name, $refresh = true ) {
		return $this->are_functions_in_call_stack( [ [ $class_name, $function_name ] ], $refresh );
	}

	/**
	 * @return array
	 */
	public function get_backtrace() {
		$options = false;

		// As of 5.3.6, 'options' parameter is a bit mask for the following options.
		if ( $this->provide_object ) {
			$options |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
		}
		if ( $this->ignore_args ) {
			$options |= DEBUG_BACKTRACE_IGNORE_ARGS;
		}

		$actual_limit          = 0 === $this->limit ? 0 : $this->limit + 4;
		$this->debug_backtrace = (array) call_user_func_array(
			$this->debug_backtrace_function,
			[
				$options,
				$actual_limit,
			]
		); // Add one item to include the current frame.

		$this->remove_frames_for_this_class();

		return $this->debug_backtrace;
	}

	private function remove_frames_for_this_class() {
		/**
		 * We cannot rely on number of frames to remove.
		 * php 5.6 and 7+ provides different call stacks.
		 * php 5.6 adds call_user_func_array from get_backtrace()
		 */
		do {
			$found = false;

			if ( ! isset( $this->debug_backtrace[0] ) ) {
				break;
			}
			$frame = $this->debug_backtrace[0];

			if (
				( isset( $frame['file'] ) && __FILE__ === $frame['file'] )
				|| ( isset( $frame['class'] ) && self::class === $frame['class'] )
			) {
				$found = true;
				$this->remove_last_frame();
			}
		} while ( $found );

		$this->remove_last_frame(); // Remove frame with the function called this class.
	}

	public function remove_last_frame() {
		if ( $this->debug_backtrace ) {
			array_shift( $this->debug_backtrace );
		}
	}
}
