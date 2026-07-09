<?php
require_once dirname( __FILE__ ) . '/wpml-admin-text-configuration.php';
require_once dirname( __FILE__ ) . '/wpml-admin-text-functionality.class.php';

use WPML\Convert\Ids;
use WPML\FP\Obj;
use WPML\ST\AdminTexts\TranslateNestedIds;

class WPML_Admin_Text_Import extends WPML_Admin_Text_Functionality {

	/** @var WPML_ST_Records $st_records */
	private $st_records;

	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	/** @var array */
	private $translatable_ids = [];

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
			$this->initialize_translatable_ids();
			foreach ( $admin_texts as $a ) {
				$type               = isset( $a['type'] ) ? $a['type'] : 'plugin';
				$admin_text_context = isset( $a['context'] ) ? $a['context'] : '';
				$admin_string_name  = $a['attr']['name'];
				if ( $this->is_blacklisted( $admin_string_name ) ) {
					continue;
				}
				if ( $this->has_translatable_ids( $a ) ) {
					$key_type = $this->get_translatable_ids_type( $a );
					$key_slug = $this->get_translatable_ids_slug( $a, $key_type );
					$key_path = '';
					$this->register_translatable_id( $admin_string_name, $key_type, $key_slug, $key_path );
					continue;
				}
				if ( ! empty( $a['key'] ) ) {
					foreach ( $a['key'] as $key ) {
						$key_name = $key['attr']['name'];
						if ( $this->has_translatable_ids( $key ) ) {
							$key_type = $this->get_translatable_ids_type( $key );
							$key_slug = $this->get_translatable_ids_slug( $key, $key_type );
							$key_path = $key_name;
							$this->register_translatable_id( $admin_string_name, $key_type, $key_slug, $key_path );
							continue;
						}
						$arr[ $admin_string_name ][ $key_name ] = isset( $key['key'] )
							? $this->read_admin_texts_recursive(
								$key['key'],
								$admin_text_context,
							  $type,
							  $arr_context,
							  $arr_type,
								$admin_string_name,
								$key_name
							)
							: 1;
						$arr_context[ $admin_string_name ]      = $admin_text_context;
						$arr_type[ $admin_string_name ]         = $type;
					}
					$arr_context[ $admin_string_name ] = $admin_text_context;
					$arr_type[ $admin_string_name ]    = $type;
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

			$_icl_admin_option_names = get_option( self::TRANSLATABLE_NAMES_SETTING );

			$arr_options = array();
			if ( isset( $arr ) && is_array( $arr ) ) {
				foreach ( $arr as $key => $v ) {
					$value = maybe_unserialize( (string) $this->get_option_without_filtering( (string) $key ) );
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

			update_option( self::TRANSLATABLE_NAMES_SETTING, $_icl_admin_option_names, 'no' );
			$this->save_translatable_ids();

			set_transient( $transient_name, $admin_texts_hash );
			$sitepress->set_setting( 'admin_text_3_2_migration_complete_' . $admin_texts_hash, true, true );
		}
	}

	/**
	 * @param array  $keys
	 * @param string $admin_text_context
	 * @param string $admin_string_type
	 * @param array  $arr_context
	 * @param array  $arr_type
	 * @param string $admin_string_name
	 * @param string $path
	 *
	 * @return array|false
	 */
	protected function read_admin_texts_recursive( $keys, $admin_text_context, $admin_string_type, &$arr_context, &$arr_type, $admin_string_name = '', $path = '' ) {
		$keys = ! empty( $keys ) && isset( $keys ['attr']['name'] ) ? array( $keys ) : $keys;
		foreach ( $keys as $key ) {
			$key_name = $key['attr']['name'];
			if ( $this->has_translatable_ids( $key ) ) {
				$key_type = $this->get_translatable_ids_type( $key );
				$key_slug = $this->get_translatable_ids_slug( $key, $key_type );
				$key_path = $path . '>' . $key_name;
				$this->register_translatable_id( $admin_string_name, $key_type, $key_slug, $key_path );
				continue;
			}
			if ( ! empty( $key['key'] ) ) {
				$arr[ $key_name ] = $this->read_admin_texts_recursive(
					$key['key'],
					$admin_text_context,
					$admin_string_type,
					$arr_context,
					$arr_type,
					$admin_string_name,
					$path . '>' . $key_name
				);
			} else {
				$arr[ $key_name ]         = 1;
				$arr_context[ $key_name ] = $admin_text_context;
				$arr_type[ $key_name ]    = $admin_string_type;
			}
		}

		return isset( $arr ) ? $arr : false;
	}

	/**
	 * @param array $entry
	 *
	 * @return bool
	 */
	private function has_translatable_ids( $entry ) {
		$entry = Obj::path( [ 'attr', 'type' ], $entry );
		return in_array( $entry, [ TranslateNestedIds::TYPE_POST_IDS, TranslateNestedIds::TYPE_TAXONOMY_IDS ], true );
	}

	/**
	 * @param array $entry
	 *
	 * @return string
	 */
	private function get_translatable_ids_type( $entry ) {
		return Obj::pathOr( TranslateNestedIds::TYPE_POST_IDS, [ 'attr', 'type' ], $entry );
	}

	/**
	 * @param array  $entry
	 * @param string $type
	 *
	 * @return string
	 */
	private function get_translatable_ids_slug( $entry, $type ) {
		return Obj::path( [ 'attr', 'sub-type' ], $entry ) ?: wpml_collect( [
			TranslateNestedIds::TYPE_POST_IDS     => Ids::ANY_POST,
			TranslateNestedIds::TYPE_TAXONOMY_IDS => Ids::ANY_TERM,
		] )->get( $type, Ids::ANY_POST );
	}

	private function initialize_translatable_ids() {
		$this->translatable_ids = get_option( self::TRANSLATABLE_ID_NAMES_SETTING, [] );
	}

	/**
	 * @param string $setting_name The name of the option holding the translatable ID
	 * @param string $type "post-ids" or "taxonomy-ids".
	 * @param string $slug e.g. "page", "category", ...
	 * @param string $path The path to the option nested value, eg. 'subkey_1>subkey_1_1>...', supports '*' wildcards. Empty means that the option itself holds the translatable IDs.
	 */
	private function register_translatable_id( $setting_name, $type, $slug, $path = '' ) {
		if ( ! array_key_exists( $setting_name, $this->translatable_ids ) ) {
			$this->translatable_ids[ $setting_name ] = [];
		}
		$this->translatable_ids[ $setting_name ][ $path ] = [
			'type' => $type,
			'slug' => $slug,
			'path' => $path,
		];
	}

	private function save_translatable_ids() {
		update_option( self::TRANSLATABLE_ID_NAMES_SETTING, $this->translatable_ids, 'no' );
	}

	private function register_string_recursive( $key, $value, $arr, $prefix, $suffix, $requires_upgrade, $type, $admin_text_context_old ) {
		if ( is_scalar( $value ) ) {
			/** @phpstan-ignore-next-line */
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
