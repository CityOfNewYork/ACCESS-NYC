<?php

class WPML_TM_Only_I_Language_Pairs_Factory implements IWPML_AJAX_Action_Loader {

	public function create() {
		global $sitepress, $wpdb;

		return new WPML_TM_Only_I_language_Pairs(
			new WPML_Language_Pair_Records( $wpdb, new WPML_Language_Records( $wpdb ) ),
			$sitepress
		);
	}
}