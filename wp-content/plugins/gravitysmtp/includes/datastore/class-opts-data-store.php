<?php

namespace Gravity_Forms\Gravity_SMTP\Data_Store;

class Opts_Data_Store implements Data_Store {

	public function get( $setting_name, $connector ) {
		$opts = $this->get_opts( $connector );

		return isset( $opts[ $setting_name ] ) ? $opts[ $setting_name ] : null;
	}

	public function save( $setting_name, $value, $connector ) {
		$opts                  = $this->get_opts( $connector );
		$opts_name             = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );
		$opts[ $setting_name ] = $value;

		update_option( $opts_name, json_encode( $opts ) );
	}

	public function save_all( $value, $connector ) {
		$opts = $this->get_opts( $connector );
		$opts_name = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );

		foreach( $value as $key => $val ) {
			if ( $val === '****************' ){
				continue;
			}

			if ( $val === 'true' ) {
				$val = true;
			}

			if ( $val === 'false' ) {
				$val = false;
			}

			$opts[ $key ] = $val;
		}

		update_option( $opts_name, json_encode( $opts ) );
	}

	public function get_opts( $connector ) {
		$opts_name = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );
		$opts      = get_option( $opts_name, '{}' );
		$opts      = json_decode( $opts, true );

		return $opts;
	}

	public function delete_all( $connector ) {
		$opts_name = sprintf( 'gravitysmtp_%s', strtolower( $connector ) );
		delete_option( $opts_name );
	}
}
