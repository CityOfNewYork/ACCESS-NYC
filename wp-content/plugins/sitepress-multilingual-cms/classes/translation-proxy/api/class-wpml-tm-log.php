<?php

class WPML_TM_Log implements WPML_TP_API_Log_Interface {
	const LOG_WP_OPTION = '_wpml_tp_api_Logger';
	const LOG_MAX_SIZE = 500;

	public function log( $action, $data = array() ) {
		$log_base_data = array(
			'timestamp' => false,
			'action'    => false,
		);

		$log_item = array_merge( $log_base_data, $data );

		$log_item['timestamp'] = date( 'Y-m-d H:i:s' );
		$log_item['action']    = $action;

		$log = $this->get_log_data();

		$log = array_slice( $log, - ( self::LOG_MAX_SIZE - 1 ) );

		$log[] = $log_item;

		$this->update_log( $log );
	}

	private function update_log( $log ) {
		return update_option( self::LOG_WP_OPTION, $log, false );
	}

	public function flush_log() {
		return update_option( self::LOG_WP_OPTION, [], false );
	}

	public function get_log_data() {
		$log = get_option( self::LOG_WP_OPTION, [] );

		return is_array( $log ) ? $log : [];
	}
}
