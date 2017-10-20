<?php

class WPML_ST_Theme_Localization_Utils {

	/** @return array */
	public function get_theme_data() {
		$themes     = wp_get_themes();
		$theme_data = array();

		foreach ( $themes as $theme_folder => $theme ) {
			$theme_data[ $theme_folder ] = array(
				'name' => $theme->get( 'Name' ),
				'TextDomain' => $theme->get( 'TextDomain' ),
			);
		}

		return $theme_data;
	}
}