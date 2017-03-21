<?php

if( !function_exists('pmai_get_join_attr') ):

function pmai_get_join_attr( $attributes = false )
{
	// validate
	if( empty($attributes) )
	{
		return '';
	}
	
	
	// vars
	$e = array();
	
	
	// loop through and render
	foreach( $attributes as $k => $v )
	{
		$e[] = $k . '="' . esc_attr( $v ) . '"';
	}
	
	
	// echo
	return implode(' ', $e);
}

endif;

?>