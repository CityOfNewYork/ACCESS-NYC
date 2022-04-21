<?php

class WPML_Installer_Domain_URL_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		global $sitepress;

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			/* @var WPML_URL_Converter $wpml_url_converter */
			global $wpml_url_converter;
			$site_url_default_lang = $wpml_url_converter->get_default_site_url();
			if ( $site_url_default_lang ) {
				return new WPML_Installer_Domain_URL( $site_url_default_lang );
			}
		}

		return null;
	}
}
