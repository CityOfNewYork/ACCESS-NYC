<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Right;
use WPML\Plugins;

class LicenseStep implements IHandler {

	public function run( Collection $data ) {
		$site_key = filter_var( $data->get( 'siteKey' ), FILTER_SANITIZE_STRING );
		icl_set_setting( 'site_key', null, true );
		if ( function_exists( 'OTGS_Installer' ) ) {
			$args = [
				'repository_id' => 'wpml',
				'nonce'         => wp_create_nonce( 'save_site_key_wpml' ),
				'site_key'      => $site_key,
				'return'        => 1,
			];
			$r    = OTGS_Installer()->save_site_key( $args );
			if ( ! empty( $r['error'] ) ) {
				return Either::left( strip_tags( $r['error'] ) );
			} else {
				icl_set_setting( 'site_key', $site_key, true );
				Plugins::updateTMAllowedOption();
				return Right::of( __( 'Thank you for registering WPML on this site. You will receive automatic updates when new versions are available.', 'sitepress' ) );
			}
		}

		return Either::left( false );
	}

}
