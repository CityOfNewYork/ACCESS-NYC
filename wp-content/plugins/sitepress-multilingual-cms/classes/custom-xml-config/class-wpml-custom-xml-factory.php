<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Custom_XML_Factory {
	public function create_resources( WPML_WP_API $wpml_wp_api ) {
		return new WPML_Custom_XML_UI_Resources( $wpml_wp_api );
	}
}
