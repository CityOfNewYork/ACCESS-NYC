<?php

class WPML_ACF_Options_Page {
	public function is_acf_options_page() {
		$is = is_admin() && isset( $_GET['page'] ) && stristr( $_GET['page'], "acf-options-" ) !== false;
		return $is;
	}
}