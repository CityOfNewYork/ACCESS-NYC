<?php

namespace WPML\TM\ATE;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use function WPML\Container\make;

class NoCreditPopup {

	/**
	 * @return string
	 */
	public function getUrl() {
		$baseUrl = make( \WPML_TM_ATE_AMS_Endpoints::class )->get_base_url( \WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS );

		return $baseUrl . '/mini_app/main.js';
	}

	/**
	 * @return array
	 */
	public function getData( $ateJobIds = null ) {
		$registration_data = make( \WPML_TM_AMS_API::class )->get_registration_data();

		$data = [
			'host'         => make( \WPML_TM_ATE_AMS_Endpoints::class )->get_base_url( \WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ),
			'wpml_host'    => get_site_url(),
			'return_url'   => \WPML\TM\API\Jobs::getCurrentUrl(),
			'secret_key'   => Obj::prop( 'secret', $registration_data ),
			'shared_key'   => Obj::prop( 'shared', $registration_data ),
			'website_uuid' => make( \WPML_TM_ATE_Authentication::class )->get_site_id(),
			'ui_language'  => make( \SitePress::class )->get_user_admin_language( User::getCurrentId() ),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
			'container'    => '#wpml-ate-console-container',
			'languages'    => $this->getLanguagesData(),
		];

		if ( $ateJobIds ) {
			$data['job_list'] = $ateJobIds;
		}

		return $data;
	}

	public function getLanguagesData() {
		$languageFields = [ 'code', 'english_name', 'native_name', 'default_locale', 'encode_url', 'tag', 'flag_url', 'display_name' ];

		return Fns::map( Obj::pick( $languageFields ), Languages::withFlags( Languages::getActive() ) );
	}
}
