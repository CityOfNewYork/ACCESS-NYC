<?php

class WPML_Installer_Domain_URL_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		global $sitepress;

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			$site_url_default_lang = $sitepress->convert_url( get_site_url(), $sitepress->get_default_language() );

			if ( $site_url_default_lang ) {
				return new WPML_Installer_Domain_URL( $site_url_default_lang );
			}
		}

		return null;
	}
}
