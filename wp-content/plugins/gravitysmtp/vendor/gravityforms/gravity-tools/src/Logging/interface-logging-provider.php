<?php

namespace Gravity_Forms\Gravity_Tools\Logging;

interface Logging_Provider {

	function log_info( $line );

	function log_debug( $line );

	function log_warning( $line );

	function log_error( $line );

	function log_fatal( $line );

	function log( $line, $priority );

	function delete_log();

	function write_line_to_log( $line );

	function get_lines();

}