<?php

namespace Gravity_Forms\Gravity_Tools\Logging\Parsers;

use Gravity_Forms\Gravity_Tools\Logging\Log_Line;

class File_Log_Parser {

	public function parse_log( $log ) {
		$lines = array_filter( explode( '[**]', $log ) );
		return array_map(  function( $line ) {
			return $this->parse_log_line( $line );
		}, $lines );
	}

	public function parse_log_line( $log_line ) {
		$log_line = trim( $log_line );
		preg_match('/(.*) - (.*) --> (.*)/', trim( $log_line ), $data );

		if ( count( $data ) !== 4 ) {
			return new Log_Line( null, null, null );
		}

		return new Log_Line( $data[1], trim( strtolower( $data[2] ) ), $data[3] );
	}

}