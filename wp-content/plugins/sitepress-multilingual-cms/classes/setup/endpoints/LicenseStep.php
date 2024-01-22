<?php

namespace WPML\Setup\Endpoint;

use OTGS_Installer_Subscription;
use WPML\Ajax\IHandler;
use WPML\API\Sanitize;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Right;
use WPML\FP\Left;
use WPML\Plugins;

class LicenseStep implements IHandler {
	const ACTION_REGISTER_SITE_KEY = 'register-site-key';
	const ACTION_GET_SITE_TYPE     = 'get-site-type';

	public function run( Collection $data ) {
		$action = $data->get( 'action' );
		switch ( $action ) {
			case self::ACTION_REGISTER_SITE_KEY:
				return $this->register_site_key( $data );
			case self::ACTION_GET_SITE_TYPE:
				return $this->get_site_type();
			default:
		}

		return $this->unexpectedError();
	}

	private function register_site_key( Collection $data ) {
		$site_key = Sanitize::string( $data->get( 'siteKey' ) );
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
				return Either::left( [ 'msg' => strip_tags( $r['error'] ) ] );
			} else {
				icl_set_setting( 'site_key', $site_key, true );
				$isTMAllowed = Plugins::updateTMAllowedOption();
				return Right::of(
					[
						'isTMAllowed' => $isTMAllowed,
						'msg'         => __( 'Thank you for registering WPML on this site. You will receive automatic updates when new versions are available.', 'sitepress' ),
					]
				);
			}
		}

		return Either::left( false );
	}

	private function get_site_type() {
		$site_type = OTGS_Installer()->repository_has_development_site_key( 'wpml' )
			? OTGS_Installer_Subscription::SITE_KEY_TYPE_DEVELOPMENT
			: OTGS_Installer_Subscription::SITE_KEY_TYPE_PRODUCTION;

		return Right::of( $site_type );
	}

	private function unexpectedError() {
		return Left::of(
			__( 'Server error. Please refresh and try again.', 'sitepress' )
		);
	}
}
