<?php

namespace Gravity_Forms\Gravity_SMTP\Data_Store;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;

class Plugin_Opts_Data_Store implements Data_Store {

	public function get( $setting_name, $connector = 'config', $default = null ) {
		$opts = $this->get_opts();

		return isset( $opts[ $setting_name ] ) ? $opts[ $setting_name ] : $default;
	}

	public function save( $setting_name, $value, $connector = 'config' ) {
		$opts_name = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );
		$opts = $this->get_opts();

		$opts[ $setting_name ] = $value;

		update_option( $opts_name, json_encode( $opts ) );
	}

	public function save_all( $value, $connector = 'config' ) {
		$opts_name = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );

		update_option( $opts_name, json_encode( $value ) );
	}

	private function get_opts() {
		$opts_name = 'gravitysmtp_config';
		$opts      = get_option( $opts_name, '{}' );
		$opts      = json_decode( $opts, true );

		return $opts;
	}
}
