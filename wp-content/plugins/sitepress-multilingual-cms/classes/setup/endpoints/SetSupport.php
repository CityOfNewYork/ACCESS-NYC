<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;

class SetSupport implements IHandler {
	const EVENT_SEND_COMPONENTS_AFTER_REGISTRATION = 'otgs_send_components_data_on_product_registration';

	public function run( Collection $data ) {
		$settingStorage = new \OTGS_Installer_WP_Share_Local_Components_Setting();
		$settingStorage->save( [ $data->get( 'repo' ) => $data->get( 'agree' ) ] );

		if ( $settingStorage->is_repo_allowed( $data->get( 'repo' ) ) ) {
			$this->schedule_send_components_data();
		}

		return Right::of( true );
	}

	private function schedule_send_components_data() {
		if ( ! wp_next_scheduled( self::EVENT_SEND_COMPONENTS_AFTER_REGISTRATION ) ) {
			wp_schedule_single_event( time() + 60, self::EVENT_SEND_COMPONENTS_AFTER_REGISTRATION );
		}
	}
}
