<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;

class SetSupport implements IHandler {
	public function run( Collection $data ) {
		$settingStorage = new \OTGS_Installer_WP_Share_Local_Components_Setting();
		$settingStorage->save( [ $data->get( 'repo' ) => $data->get( 'agree' ) ] );

		return Right::of( true );
	}
}
