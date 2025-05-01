<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AAL_Hook_Plugins extends AAL_Hook_Base {

	protected function _add_log_plugin( $action, $plugin_name ) {
		$plugin_version = '';

		// Get plugin name if is a path
		if ( false !== strpos( $plugin_name, '/' ) ) {
			$plugin_dir  = explode( '/', $plugin_name );
			$plugin_data = array_values( get_plugins( '/' . $plugin_dir[0] ) );
			$plugin_data = array_shift( $plugin_data );
			$plugin_name = $plugin_data['Name'];

			if ( ! empty( $plugin_data['Version'] ) ) {
				$plugin_version = $plugin_data['Version'];
			}
		}

		aal_insert_log(
			array(
				'action'      => $action,
				'object_type' => 'Plugins',
				'object_id'   => 0,
				'object_name' => $plugin_name,
				'object_subtype' => $plugin_version,
			)
		);
	}

	public function hooks_deactivated_plugin( $plugin_name ) {
		$this->_add_log_plugin( 'deactivated', $plugin_name );
	}

	public function hooks_activated_plugin( $plugin_name ) {
		$this->_add_log_plugin( 'activated', $plugin_name );
	}
	
	public function hooks_delete_plugin( $plugin_file ) {
		$this->_add_log_plugin( 'deleted', $plugin_file );
	}

	/**
	 * @param Plugin_Upgrader $upgrader
	 * @param array $extra
	 */
	public function hooks_plugin_install_or_update( $upgrader, $extra ) {
		if ( ! isset( $extra['type'] ) || 'plugin' !== $extra['type'] )
			return;

		if ( 'install' === $extra['action'] ) {
			$path = $upgrader->plugin_info();
			if ( ! $path )
				return;
			
			$data = get_plugin_data( $upgrader->skin->result['local_destination'] . '/' . $path, true, false );
			
			aal_insert_log(
				array(
					'action' => 'installed',
					'object_type' => 'Plugins',
					'object_name' => $data['Name'],
					'object_subtype' => $data['Version'],
				)
			);
		}

		if ( 'update' === $extra['action'] ) {
			if ( isset( $extra['bulk'] ) && true == $extra['bulk'] ) {
				$slugs = $extra['plugins'];
			} else {
				$plugin_slug = isset( $upgrader->skin->plugin ) ? $upgrader->skin->plugin : $extra['plugin'];

				if ( empty( $plugin_slug ) ) {
					return;
				}

				$slugs = array( $plugin_slug );
			}
			
			foreach ( $slugs as $slug ) {
				$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug, true, false );
				
				aal_insert_log(
					array(
						'action' => 'updated',
						'object_type' => 'Plugins',
						'object_name' => $data['Name'],
						'object_subtype' => $data['Version'],
					)
				);
			}
		}
	}

	public function hooks_auto_update_settings( $option, $value, $old_value ) {
		$enabled_plugins = array_diff( $value, $old_value );
		$disabled_plugins = array_diff( $old_value, $value );

		foreach ( $disabled_plugins as $plugin ) {
			$this->_add_log_plugin( 'auto_update_disabled', $plugin );
		}

		foreach ( $enabled_plugins as $plugin ) {
			$this->_add_log_plugin( 'auto_update_enabled', $plugin );
		}
	}

	public function __construct() {
		add_action( 'activated_plugin', array( $this, 'hooks_activated_plugin' ) );
		add_action( 'deactivated_plugin', array( $this, 'hooks_deactivated_plugin' ) );
		
		add_action( 'delete_plugin', array( $this, 'hooks_delete_plugin' ) );

		add_action( 'upgrader_process_complete', array( $this, 'hooks_plugin_install_or_update' ), 10, 2 );

		add_action( 'update_site_option_auto_update_plugins', [ $this, 'hooks_auto_update_settings' ], 10, 3 );

		parent::__construct();
	}
	
}
