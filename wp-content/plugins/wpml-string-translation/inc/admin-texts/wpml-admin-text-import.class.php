<?php
require_once dirname( __FILE__ ) . '/wpml-admin-text-configuration.php';
require_once dirname( __FILE__ ) . '/wpml-admin-text-functionality.class.php';

class WPML_Admin_Text_Import extends WPML_Admin_Text_Functionality {

	/** @var WPML_ST_Records $st_records */
	private $st_records;

	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	function __construct( WPML_ST_Records $st_records, WPML_WP_API $wp_api ) {
		$this->st_records = $st_records;
		$this->wp_api     = $wp_api;
	}

	/**
	 * @param array  $admin_texts
	 * @param string $config_handler_hash
	 */
	function parse_config( array $admin_texts, $config_handler_hash ) {

		$admin_texts_hash = md5( serialize( $admin_texts ) );
		$transient_name   = 'wpml_admin_text_import:parse_config:' . $config_handler_hash;

		if ( $this->wp_api->is_string_translation_page() || get_transient( $transient_name ) !== $admin_texts_hash ) {
			global $iclTranslationManagement, $sitepress;
			foreach ( $admin_texts as $a ) {
				$type               = isset( $a['type'] ) ? $a['type'] : 'plugin';
				$admin_text_context = isset( $a['context'] ) ? $a['context'] : '';
				$admin_string_name  = $a['attr']['name'];
				if ( $this->is_blacklisted( $admin_string_name ) ) {
					continue;
				}
				if ( ! empty( $a['key'] ) ) {
					foreach ( $a['key'] as $key ) {
						$arr[ $admin_string_name ][ $key['attr']['name'] ] = isset( $key['key'] )
							? $this->read_admin_texts_recursive( $key['key'],
							                                     $admin_text_context,
							                                     $type,
							                                     $arr_context,
							                                     $arr_type )
							: 1;
						$arr_context[ $admin_string_name ]                 = $admin_text_context;
						$arr_type[ $admin_string_name ]                    = $type;
					}
				} else {
					$arr[ $admin_string_name ]         = 1;
					$arr_context[ $admin_string_name ] = $admin_text_context;
					$arr_type[ $admin_string_name ]    = $type;
				}
			}

			if ( isset( $arr ) ) {
				$iclTranslationManagement->admin_texts_to_translate = array_merge( $iclTranslationManagement->admin_texts_to_translate,
				                                                                   $arr );
			}

			$_icl_admin_option_names = get_option( '_icl_admin_option_names' );

			$arr_options = array();
			if ( isset( $arr ) && is_array( $arr ) ) {
				foreach ( $arr as $key => $v ) {
					$value = maybe_unserialize( $this->get_option_without_filtering( $key ) );
					$value = is_array( $value ) && is_array( $v ) ? array_intersect_key( $value, $v ) : $value;
					$admin_text_context = isset( $arr_context[ $key ] ) ? $arr_context[ $key ] : '';
					$type               = isset( $arr_type[ $key ] ) ? $arr_type[ $key ] : '';

					$req_upgrade = ! $sitepress->get_setting( 'admin_text_3_2_migration_complete_' . $admin_texts_hash, false );
					if ( (bool) $value === true ) {
						$this->register_string_recursive( $key,
						                                  $value,
						                                  $arr[ $key ],
						                                  '',
						                                  $key,
						                                  $req_upgrade,
						                                  $type,
						                                  $admin_text_context );
					}
					$arr_options[ $key ] = $v;
				}

				$_icl_admin_option_names = is_array( $_icl_admin_option_names )
					? array_replace_recursive( $_icl_admin_option_names, $arr_options ) : $arr_options;
			}

			update_option( '_icl_admin_option_names', $_icl_admin_option_names );

			set_transient( $transient_name, $admin_texts_hash );
			$sitepress->set_setting( 'admin_text_3_2_migration_complete_' . $admin_texts_hash, true, true );
		}
	}


	private function register_string_recursive( $key, $value, $arr, $prefix, $suffix, $requires_upgrade, $type, $admin_text_context_old ) {
		if ( is_scalar( $value ) ) {
			icl_register_string( WPML_Admin_Texts::DOMAIN_NAME_PREFIX . $suffix, $prefix . $key, $value, true );
			if ( $requires_upgrade ) {
				$this->migrate_3_2( $type, $admin_text_context_old, $suffix, $prefix . $key );
			}
		} elseif ( ! is_null( $value ) ) {
			foreach ( $value as $sub_key => $sub_value ) {
				if ( isset( $arr[ $sub_key ] ) ) {
					$this->register_string_recursive( $sub_key,
					                                  $sub_value,
					                                  $arr[ $sub_key ],
					                                  $prefix . '[' . $key . ']',
					                                  $suffix,
					                                  $requires_upgrade,
					                                  $type,
					                                  $admin_text_context_old );
				}
			}
		}
	}

	private function migrate_3_2( $type, $old_admin_text_context, $new_admin_text_context, $key ) {
		global $wpdb;

		$old_string_id = icl_st_is_registered_string( WPML_Admin_Texts::DOMAIN_NAME_PREFIX . $type . '_' . $old_admin_text_context, $key );
		if ( $old_string_id ) {
			$new_string_id = icl_st_is_registered_string( WPML_Admin_Texts::DOMAIN_NAME_PREFIX . $new_admin_text_context, $key );
			if ( $new_string_id ) {
				$wpdb->update( $wpdb->prefix . 'icl_string_translations', array( 'string_id' => $new_string_id ), array( 'string_id' => $old_string_id ) );
				$this->st_records->icl_strings_by_string_id( $new_string_id )
				                 ->update(
					                 array(
						                 'status' => $this->st_records
							                 ->icl_strings_by_string_id( $old_string_id )
							                 ->status()
					                 )
				                 );
			}
		}
	}
}
