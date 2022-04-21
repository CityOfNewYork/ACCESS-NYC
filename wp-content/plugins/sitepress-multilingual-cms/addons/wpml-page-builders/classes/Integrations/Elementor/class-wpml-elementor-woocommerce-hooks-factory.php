<?php

class WPML_Elementor_WooCommerce_Hooks_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		return new WPML_Elementor_WooCommerce_Hooks();
	}
}