<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_Check_Website_ID implements IWPML_Action {

	/** @var WPML_Option_Manager $option_manager */
	private $option_manager;

	/** @var WPML_TM_ATE_API $ate_api */
	private $ate_api;

	/** @var WPML_TM_AMS_API $ams_api */
	private $ams_api;

	public function __construct(
		WPML_Option_Manager $option_manager,
		WPML_TM_ATE_API $ate_api,
		WPML_TM_AMS_API $ams_api
	) {
		$this->option_manager = $option_manager;
		$this->ate_api        = $ate_api;
		$this->ams_api        = $ams_api;
	}

	public function add_hooks() {
		add_action( 'wpml_after_tm_loaded', array( $this, 'do_check' ) );
	}

	/**
	 * Check if the stored site id is different from the one returned by ams api and
	 * then:
	 * 1) test if the ams one works
	 * 2) Update stored id if test is successful
	 */
	public function do_check() {
		$stored_site_id   = wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE );
		$site_id_from_ams = $this->ate_api->get_website_id( get_site_url() );

		if ( $site_id_from_ams && $stored_site_id !== $site_id_from_ams ) {
			if ( $this->does_site_id_work( $site_id_from_ams ) ) {
				update_option( WPML_Site_ID::SITE_ID_KEY . ':ate', $site_id_from_ams, false );
			}
		}
		$this->option_manager->set( 'TM-has-run', 'WPML_TM_AMS_Check_Website_ID', true, true );
	}

	/**
	 * @param string $site_id
	 *
	 * @return bool
	 */
	private function does_site_id_work( $site_id ) {
		$this->ams_api->override_site_id( $site_id );
		$response = $this->ams_api->is_subscription_activated( 'any@any.com' );

		$is_site_id_error = false;

		if ( is_wp_error( $response ) ) {
			$error_data       = $response->get_error_data( 400 );
			$is_site_id_error = isset( $error_data['detail'] ) && $error_data['detail'] === 'Website not found, please validate site identifier';
		}

		return ! $is_site_id_error;
	}


}
