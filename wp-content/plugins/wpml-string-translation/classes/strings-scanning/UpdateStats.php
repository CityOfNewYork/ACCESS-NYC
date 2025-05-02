<?php

namespace WPML\ST\StringsScanning;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\FP\Either;

class UpdateStats implements IHandler {

	public function run( Collection $data ) {
		$stats = $this->sanitizeStats( $data->get( 'stats', [] ) );
		$st    = apply_filters( 'wpml_get_setting', false, 'st' );

		foreach ( $stats as $name => $data ) {
			$count = $data['count'];
			$key   = $data['settingsKey'];
			$item  = $data['pluginOrThemeId'];

			$old_count = isset( $st[ $key ][ $item ][ $name ] ) ?
				$st[ $key ][ $item ][ $name ] :
				0;

			$st[ $key ][ $item ][ $name ] = $old_count + $count;
		}

		do_action( 'wpml_set_setting', 'st', $st, true );

		return Either::of( [] );
	}

	private function sanitizeStats( $origStats ) {
		$stats = [];

		foreach ( $origStats as $origKey => $data ) {
			$key = sanitize_text_field( $origKey );
			$data['count']           = (int) $data['count'];
			$data['pluginOrThemeId'] = sanitize_text_field( $data['pluginOrThemeId'] );
			$data['settingsKey']     = sanitize_text_field( $data['settingsKey'] );
			$stats[ $key ]           = $data;
		}

		return $stats;
	}
}
