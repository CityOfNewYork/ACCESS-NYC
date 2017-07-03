<?php

class WPML_ST_Package_Factory {

	public function create( $package_data ) {
		return new WPML_Package( $package_data );
	}
}
