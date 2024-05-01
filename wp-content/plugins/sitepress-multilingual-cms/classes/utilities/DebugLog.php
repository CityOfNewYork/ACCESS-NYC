<?php

namespace WPML\Utilities;

class DebugLog implements \IWPML_Backend_Action, \IWPML_AJAX_Action, \IWPML_REST_Action {

	public static $trace;

	public function add_hooks() {
		if ( ! defined( 'WPML_DEBUG_LOG' ) || ! WPML_DEBUG_LOG ) {
			return;
		}
		add_action( 'shutdown', [$this, 'onShutdown'] );
	}

	public static function storeBackTrace() {
		if ( ! defined( 'WPML_DEBUG_LOG' ) || ! WPML_DEBUG_LOG ) {
			return;
		}

		$log_entry = sprintf(
			"%s [WPML Logs] - Req [%s] - URI [%s] - Message: %s",
			time(),
			$_SERVER['REQUEST_METHOD'] ?? '',
			$_SERVER['REQUEST_URI'] ?? '',
			print_r(debug_backtrace(0, 25), true)
		);

		static::$trace[] = $log_entry;
	}

	public function onShutdown() {
		if ( ! static::$trace ) {
			return;
		}

		$fp = fopen(is_string( constant( 'WPML_DEBUG_LOG' ) ) ? constant( 'WPML_DEBUG_LOG' ) : ABSPATH . 'debug.wpml.log', 'a+');
		if ( ! $fp ) {
			return;
		}

		fwrite( $fp, implode("\r\n ", static::$trace ) . PHP_EOL );
		fclose($fp);
	}
}
