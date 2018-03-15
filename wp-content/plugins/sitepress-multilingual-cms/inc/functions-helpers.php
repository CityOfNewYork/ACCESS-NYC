<?php
if ( ! function_exists( 'object_to_array' ) ) {
	function object_to_array( $obj ) {
		return json_decode( json_encode( $obj ), true );
	}
}
