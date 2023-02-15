<?php

namespace OTGS\Installer\Loader;

class Config {

	public static function merge( array $delegate, array $wpInstallerInstances ) {
		$args_to_merge = [ 'site_key_nags' ];
		foreach ( $wpInstallerInstances as $instance ) {
			if ( $instance['bootfile'] !== $delegate['bootfile'] ) {
				foreach ( $args_to_merge as $arg ) {
					if ( isset( $instance['args'][ $arg ] ) && is_array( $instance['args'][ $arg ] ) ) {
						if ( isset( $delegate['args'][ $arg ] ) ) {
							$delegate['args'][ $arg ] = array_merge_recursive( $delegate['args'][ $arg ], $instance['args'][ $arg ] );
						} else {
							$delegate['args'][ $arg ] = $instance['args'][ $arg ];
						}
					}
				}
			}
		}

		return $delegate;
	}
}
