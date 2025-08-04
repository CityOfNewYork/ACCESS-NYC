<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

abstract class Fast_Endpoint {

	public function __construct() {
		$this->load_wp();

		foreach( $this->extra_includes() as $file_to_include ) {
			require_once( $file_to_include );
		}
	}

	public abstract function run();

	protected function extra_includes() {
		return array();
	}

	private function load_wp() {
		$found = false;
		$cwd = dirname( __FILE__ );

		while( ! $found ) {
			$checked_path = $cwd . '/wp-load.php';

			if ( file_exists( $checked_path ) ) {
				require_once( $checked_path );
				$found = true;
			}

			$cwd = dirname( $cwd );
		}

		require_once( ABSPATH . WPINC . '/default-constants.php' );
		require_once( ABSPATH . WPINC . '/class-wp-textdomain-registry.php' );
		require_once( ABSPATH . WPINC . '/capabilities.php' );
		require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
		require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );
		require_once( ABSPATH . WPINC . '/class-wp-role.php' );
		require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
		require_once( ABSPATH . WPINC . '/class-wp-user.php' );
		require_once( ABSPATH . WPINC . '/l10n.php' );
		require_once( ABSPATH . WPINC . '/user.php' );
		require_once( ABSPATH . WPINC . '/pluggable.php' );
		require_once( ABSPATH . WPINC . '/rest-api.php' );
		require_once( ABSPATH . WPINC . '/kses.php' );
		require_once( ABSPATH . WPINC . '/blocks.php' );
		require_once( ABSPATH . WPINC . '/theme.php' );

		wp_plugin_directory_constants();
		wp_cookie_constants();

		$GLOBALS['wp_textdomain_registry'] = new \WP_Textdomain_Registry();
	}
}