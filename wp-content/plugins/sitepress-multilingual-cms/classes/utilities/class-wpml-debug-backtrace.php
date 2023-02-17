<?php

/**
 * Class WPML_Debug_BackTrace
 *
 * @deprecated 4.2.8
 */
class WPML_Debug_BackTrace extends WPML\Utils\DebugBackTrace {

	/**
	 * @param string $php_version Deprecated.
	 * @param int    $limit
	 * @param bool   $provide_object
	 * @param bool   $ignore_args
	 * @param string $debug_backtrace_function
	 * @phpstan-ignore-next-line
	 */
	public function __construct(
		$php_version = null,
		$limit = 0,
		$provide_object = false,
		$ignore_args = true,
		$debug_backtrace_function = null
	) {
		parent::__construct( $limit, $provide_object, $ignore_args, $debug_backtrace_function );
	}
}
