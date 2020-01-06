<?php

class WPML_ST_JED_Domain {

	public static function get( $domain, $handler ) {
		return $domain . '-' . $handler;
	}
}
