<?php

use \WPML\FP\Fns;
use \WPML\TM\Jobs\FieldId;
use \WPML\FP\Logic;
use \WPML\FP\Lst;
use \WPML\FP\Either;
use \WPML\LIB\WP\Post;
use function \WPML\FP\curryN;
use function \WPML\FP\pipe;
use function \WPML\FP\invoke;
use WPML\TM\Jobs\Utils;
use WPML\FP\Relation;

/**
 * Class WPML_Element_Translation_Package
 *
 * @package wpml-core
 */
class WPML_Element_Translation_Package extends WPML_Translation_Job_Helper {

	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	/**
	 * The constructor.
	 *
	 * @param WPML_WP_API $wp_api An instance of the WP API.
	 */
	public function __construct( WPML_WP_API $wp_api = null ) {
		global $sitepress;
		if ( $wp_api ) {
			$this->wp_api = $wp_api;
		} else {
			$this->wp_api = $sitepress->get_wp_api();
		}
	}

	/**
	 * Create translation package
	 *
	 * @param \WPML_Package|\WP_Post|int $post
	 * @param bool                       $isOriginal
	 *
	 * @return array<string,string|array<string,string>>
	 */
	public function create_translation_package( $post, $isOriginal = false ) {

		$package = array();
		$post    = is_numeric( $post ) ? get_post( $post ) : $post;
		if ( apply_filters( 'wpml_is_external', false, $post ) ) {
			/** @var stdClass $post */
			$post_contents = (array) $post->string_data;
			$original_id   = isset( $post->post_id ) ? $post->post_id : $post->ID;
			$type          = 'external';

			if ( isset( $post->title ) ) {
				$package['title'] = apply_filters( 'wpml_tm_external_translation_job_title', $post->title, $original_id );
			}

			if ( empty( $package['title'] ) ) {
				$package['title'] = sprintf(
					/* translators: The placeholder will be replaced with a number (an ID) */
					__( 'External package ID: %d', 'wpml-translation-management' ),
					$original_id
				);
			}
		} else {
			$home_url         = get_home_url();
			$package['url']   = htmlentities( $home_url . '?' . ( 'page' === $post->post_type ? 'page_id' : 'p' ) . '=' . ( $post->ID ) );
			$package['title'] = $post->post_title;

			$post_contents = array(
				'title'   => $post->post_title,
				'body'    => $post->post_content,
				'excerpt' => $post->post_excerpt,
			);

			if ( wpml_get_setting_filter( false, 'translated_document_page_url' ) === 'translate' ) {
				$post_contents['URL'] = $post->post_name;
			}

			$original_id = $post->ID;

			$custom_fields_to_translate = \WPML\TM\Settings\Repository::getCustomFieldsToTranslate();

			if ( ! empty( $custom_fields_to_translate ) ) {
				$package = $this->add_custom_field_contents(
					$package,
					$post,
					$custom_fields_to_translate,
					$this->get_tm_setting( array( 'custom_fields_encoding' ) )
				);
			}

			$post_contents = array_merge( $post_contents, $this->get_taxonomy_fields( $post ) );
			$type          = 'post';
		}
		$package['contents']['original_id'] = array(
			'translate' => 0,
			'data'      => $original_id,
		);
		$package['type']                    = $type;

		$package['contents'] = $this->buildEntries( $package['contents'], $post_contents );

		return apply_filters( 'wpml_tm_translation_job_data', $package, $post, $isOriginal );
	}

	private function buildEntries( $contents, $entries, $parentKey = '' ) {
		foreach ( $entries as $key => $entry ) {
			$fullKey = $parentKey ? $parentKey . '_' . $key : $key;

			if ( is_array( $entry ) ) {
				$contents = $this->buildEntries( $contents, $entry, $fullKey );
			} else {
				$contents[ $fullKey ] = [
					'translate' => 1,
					'data'      => base64_encode( $entry ),
					'format'    => 'base64',
				];
			}
		}

		return $contents;
	}

	/**
	 * @param array $translation_package
	 * @param int   $job_id
	 * @param array $prev_translation
	 */
	public function save_package_to_job( array $translation_package, $job_id, $prev_translation ) {
		global $wpdb;

		$show = $wpdb->hide_errors();

		foreach ( $translation_package['contents'] as $field => $value ) {
			$job_translate = array(
				'job_id'                => $job_id,
				'content_id'            => 0,
				'field_type'            => $field,
				'field_wrap_tag'        => isset( $value['wrap_tag'] ) ? $value['wrap_tag'] : '',
				'field_format'          => isset( $value['format'] ) ? $value['format'] : '',
				'field_translate'       => $value['translate'],
				'field_data'            => $value['data'],
				'field_data_translated' => '',
				'field_finished'        => 0,
			);

			if ( array_key_exists( $field, $prev_translation ) ) {
				$job_translate['field_data_translated'] = $prev_translation[ $field ]->get_translation();
				$job_translate['field_finished']        = $prev_translation[ $field ]->is_finished( $value['data'] );
			}

			$job_translate = $this->filter_non_translatable_fields( $job_translate );

			$wpdb->insert( $wpdb->prefix . 'icl_translate', $job_translate );
		}

		$wpdb->show_errors( $show );
	}

	/**
	 * @param array $job_translate
	 *
	 * @return mixed|void
	 */
	private function filter_non_translatable_fields( $job_translate ) {

		if ( $job_translate['field_translate'] ) {
			$data = $job_translate['field_data'];
			if ( 'base64' === $job_translate['field_format'] ) {
				// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$data = base64_decode( $data );
			}
			$is_translatable = ! WPML_String_Functions::is_not_translatable( $data ) && apply_filters( 'wpml_translation_job_post_meta_value_translated', 1, $job_translate['field_type'] );
			$is_translatable = (bool) apply_filters( 'wpml_tm_job_field_is_translatable', $is_translatable, $job_translate );
			if ( ! $is_translatable ) {
				$job_translate['field_translate']       = 0;
				$job_translate['field_data_translated'] = $job_translate['field_data'];
				$job_translate['field_finished']        = 1;
			}
		}

		return $job_translate;
	}

	/**
	 * @param object $job
	 * @param int    $post_id
	 * @param array  $fields
	 */
	public function save_job_custom_fields( $job, $post_id, $fields ) {
		$decode_translation = function ( $translation ) {
			// always decode html entities  eg decode &amp; to &.
			return html_entity_decode( str_replace( '&#0A;', "\n", $translation ) );
		};

		$get_field_id = function( $field_name, $el_data ) {
			if (
				strpos( $el_data->field_data, (string) $field_name ) === 0
				&& 1 === preg_match( '/field-(.*?)-name/U', $el_data->field_type, $match )
				&& 1 === preg_match( '/field-' . $field_name . '-[0-9].*?-name/', $el_data->field_type )
			) {
				return $match[1];
			}
			return null;
		};

		$field_names = [];

		foreach ( $fields as $field_name => $val ) {
			if ( '' === (string) $field_name ) {
				continue;
			}

			// find it in the translation.
			foreach ( $job->elements as $el_data ) {
				$field_id_string = $get_field_id( $field_name, $el_data );
				if ( $field_id_string ) {
					$field_names[ $field_name ] = isset( $field_names[ $field_name ] )
						? $field_names[ $field_name ] : [];

					$field_translation = false;
					foreach ( $job->elements as $v ) {
						if ( 'field-' . $field_id_string === $v->field_type ) {
							$field_translation = $this->decode_field_data(
								$v->field_data_translated,
								$v->field_format
							);
						}
						if ( 'field-' . $field_id_string . '-type' === $v->field_type ) {
							$field_type = $v->field_data;
							break;
						}
					}
					if ( false !== $field_translation && isset( $field_type ) && 'custom_field' === $field_type ) {
						$field_id_string = $this->remove_field_name_from_start( $field_name, $field_id_string );
						$meta_keys       = wpml_collect( explode( '-', $field_id_string ) )
							->map( [ 'WPML_TM_Field_Type_Encoding', 'decode_hyphen' ] )
							->prepend( $field_name )
							->toArray();
						$field_names     = Utils::insertUnderKeys( $meta_keys, $field_names, $decode_translation( $field_translation ) );
					}
				}
			}
		}

		$this->save_custom_field_values( $field_names, $post_id, $job->original_doc_id );
	}

	/**
	 * Remove the field from the start of the string.
	 *
	 * @param string $field_name The field to remove.
	 * @param string $field_id_string The full field identifier.
	 * @return string
	 */
	private function remove_field_name_from_start( $field_name, $field_id_string ) {
		return preg_replace( '#' . $field_name . '-?#', '', $field_id_string, 1 );
	}

	/**
	 * @param array $fields_in_job
	 * @param int   $post_id
	 * @param int   $original_post_id
	 */
	private function save_custom_field_values( $fields_in_job, $post_id, $original_post_id ) {
		$encodings = $this->get_tm_setting( array( 'custom_fields_encoding' ) );
		foreach ( $fields_in_job as $name => $contents ) {
			$this->wp_api->delete_post_meta( $post_id, $name );

			$contents = (array) $contents;
			$single   = count( $contents ) === 1;
			$encoding = isset( $encodings[ $name ] ) ? $encodings[ $name ] : '';

			foreach ( $contents as $value ) {

				$value = self::preserve_numerics( $value, $name, $original_post_id, $single );
				$value = $encoding ? WPML_Encoding::encode( $value, $encoding ) : $value;
				$value = apply_filters( 'wpml_encode_custom_field', $value, $name );
				$value = $this->prevent_strip_slash_on_json( $value, $encoding );

				$this->wp_api->add_post_meta( $post_id, $name, $value, $single );
			}
		}
	}

	/**
	 * The core function `add_post_meta` always performs
	 * a `stripslashes_deep` on the value. We need to escape
	 * once more before to call the function.
	 *
	 * @param string $value
	 * @param string $encoding
	 *
	 * @return string
	 */
	private function prevent_strip_slash_on_json( $value, $encoding ) {
		if ( in_array( 'json', explode( ',', $encoding ), true ) ) {
			$value = wp_slash( $value );
		}

		return $value;
	}

	/**
	 * @param array  $package
	 * @param object $post
	 * @param array  $fields_to_translate
	 * @param array  $fields_encoding
	 *
	 * @return array
	 */
	private function add_custom_field_contents( $package, $post, $fields_to_translate, $fields_encoding ) {
		foreach ( $fields_to_translate as $key ) {
			$encoding             = isset( $fields_encoding[ $key ] ) ? $fields_encoding[ $key ] : '';
			$custom_fields_values = array_values( array_filter( get_post_meta( $post->ID, $key ) ) );
			foreach ( $custom_fields_values as $index => $custom_field_val ) {
				$custom_field_val = apply_filters( 'wpml_decode_custom_field', $custom_field_val, $key );
				$package          = $this->add_single_field_content( $package, $key, array( $index ), $custom_field_val, $encoding );
			}
		}

		return $package;
	}

	/**
	 * For array valued custom fields cf is given in the form field-{$field_name}-join('-', $indicies)
	 *
	 * @param array                 $package
	 * @param string                $key
	 * @param array                 $custom_field_index
	 * @param array|stdClass|string $custom_field_val
	 * @param string                $encoding
	 *
	 * @return array
	 */
	private function add_single_field_content( $package, $key, $custom_field_index, $custom_field_val, $encoding ) {
		if ( $encoding && is_scalar( $custom_field_val ) ) {
			$custom_field_val = WPML_Encoding::decode( $custom_field_val, $encoding );
			$encoding         = '';
		}
		if ( is_scalar( $custom_field_val ) ) {
			list( $cf, $key_index ) = WPML_TM_Field_Type_Encoding::encode( $key, $custom_field_index );
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$package['contents'][ $cf ]           = array(
				'translate' => 1,
				'data'      => base64_encode( (string) $custom_field_val ),
				'format'    => 'base64',
			);
			$package['contents'][ $cf . '-name' ] = array(
				'translate' => 0,
				'data'      => $key_index,
			);
			$package['contents'][ $cf . '-type' ] = array(
				'translate' => 0,
				'data'      => 'custom_field',
			);

		} else {
			foreach ( (array) $custom_field_val as $ind => $value ) {
				$package = $this->add_single_field_content(
					$package,
					$key,
					array_merge( $custom_field_index, array( $ind ) ),
					$value,
					$encoding
				);
			}
		}

		return $package;
	}

	/**
	 * Ensure that any numerics are preserved in the given value. eg any string like '10'
	 * will be converted to an integer if the corresponding original value was an integer.
	 *
	 * @param mixed      $value
	 * @param string     $name
	 * @param string|int $original_post_id
	 * @param bool       $single
	 *
	 * @return mixed
	 */
	public static function preserve_numerics( $value, $name, $original_post_id, $single ) {
		$get_original = function () use ( $original_post_id, $name, $single ) {
			$meta = get_post_meta( (int) $original_post_id, $name, $single );
			return apply_filters( 'wpml_decode_custom_field', $meta, $name );
		};
		if ( is_numeric( $value ) && is_int( $get_original() ) ) {
			$value = intval( $value );
		} elseif ( is_array( $value ) ) {
			$value = self::preserve_numerics_recursive( $get_original(), $value );
		}

		return $value;
	}

	/**
	 * Ensure that any numerics are preserved in the given value. eg any string like '10'
	 * will be converted to an integer if the corresponding original value was an integer.
	 *
	 * @param mixed $original
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	private static function preserve_numerics_recursive( $original, $value ) {
		if ( is_array( $original ) ) {
			foreach ( $original as $key => $data ) {
				if ( isset( $value[ $key ] ) ) {
					if ( is_array( $data ) ) {
						$value[ $key ] = self::preserve_numerics_recursive( $data, $value[ $key ] );
					} elseif ( is_int( $data ) && is_numeric( $value[ $key ] ) ) {
						$value[ $key ] = intval( $value[ $key ] );
					}
				}
			}
		}

		return $value;
	}

	private function get_taxonomy_fields( $post ) {
		global $sitepress;

		$termMetaKeysToTranslate = self::getTermMetaKeysToTranslate();

		// $getTermFields :: WP_Term → [[fieldId, fieldVal]]
		$getTermFields = function ( $term ) {
			return [
				[ FieldId::forTerm( $term->term_taxonomy_id ), $term->name ],
				[ FieldId::forTermDescription( $term->term_taxonomy_id ), $term->description ],
			];
		};

		// $getTermMetaFields :: [metakeys] → WP_Term → [[fieldId, fieldVal]]
		$getTermMetaFields = curryN(
			2,
			function ( $termMetaKeysToTranslate, $term ) {

				// $getMeta :: int → string → object
				$getMeta = curryN(
					2,
					function ( $termId, $key ) {
						return (object) [
							'id'   => $termId,
							'key'  => $key,
							'meta' => get_term_meta( $termId, $key ),
						];
					}
				);

				// $hasMeta :: object → bool
				$hasMeta = function ( $termData ) {
					return isset( $termData->meta[0] );
				};

				// $makeField :: object → [fieldId, $fieldVal]
				$makeField = function ( $termData ) {
					return [ FieldId::forTermMeta( $termData->id, $termData->key ), $termData->meta[0] ];
				};

				// $get :: [metakeys] → [[fieldId, $fieldVal]]
				$get = pipe(
					Fns::map( $getMeta( $term->term_taxonomy_id ) ),
					Fns::filter( $hasMeta ),
					Fns::map( $makeField )
				);

				return $get( $termMetaKeysToTranslate );
			}
		);

		// $getAll :: [WP_Term] → [[fieldId, fieldVal]]
		$getAll = Fns::converge( Lst::concat(), [ $getTermFields, $getTermMetaFields( $termMetaKeysToTranslate ) ] );

		return wpml_collect( $sitepress->get_translatable_taxonomies( false, $post->post_type ) ) // [taxonomies]
			->map( Post::getTerms( $post->ID ) ) // [Either false|WP_Error [WP_Term]]
			->filter( Fns::isRight() ) // [Right[WP_Term]]
			->map( invoke( 'get' ) ) // [[WP_Term]]
			->flatten() // [WP_Term]
			->map( $getAll ) // [[fieldId, fieldVal]]
			->mapWithKeys( Lst::fromPairs() ) // [fieldId => fieldVal]
			->toArray();
	}

	public static function getTermMetaKeysToTranslate() {
		$fieldTranslation = new WPML_Custom_Field_Setting_Factory( self::get_core_translation_management() );

		$settingsFactory      = self::get_core_translation_management()->settings_factory();

		$translatableMetaKeys = pipe(
			[ $settingsFactory, 'term_meta_setting' ],
			invoke( 'status' ),
			Relation::equals( WPML_TRANSLATE_CUSTOM_FIELD )
		);

		return wpml_collect( $fieldTranslation->get_term_meta_keys() )
			->filter( $translatableMetaKeys )
			->values()
			->toArray();
	}
}
