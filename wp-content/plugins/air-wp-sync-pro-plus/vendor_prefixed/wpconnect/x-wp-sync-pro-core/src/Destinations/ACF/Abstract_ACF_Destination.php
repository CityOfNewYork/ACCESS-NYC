<?php
/**
 * Manages import as ACF field.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\ACF;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Abstract_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations\Format_Data_Destination;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;

/**
 * ACF_Destination class.
 */
abstract class Abstract_ACF_Destination extends Abstract_Destination {
	use Format_Data_Destination;

	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'acf';


	/**
	 * Map ACF types if local types.
	 *
	 * Array[][{acf_type}] a config as array it should contain the key 'supported_sources' or 'sub_fields' if the field is a composed one (e.g ACF Link field).
	 * Array[][{acf_type}]['supported_sources'] an array of supported sources types (defined in Formatter).
	 * Array[][{acf_type}]['format'] (optional) a function to format the value returned by the local type to be compatible the value ACF is expecting for this field.
	 * Array[][{acf_type}]['extra_filter'] (optional) a function to set the field config at runtime (e.g if the config should change based on ACF field options)
	 * Array[][{acf_type}]['sub_fields'] an array of subfields.
	 * Array[][{acf_type}]['sub_fields'][{subfield key}] subfield config
	 * Array[][{acf_type}]['sub_fields'][{subfield key}]['label'] a label to be displayed in the connection screen
	 * Array[][{acf_type}]['sub_fields'][{subfield key}]['supported_sources'] an array of supported sources types (defined in Formatter).
	 *
	 * @var array
	 */
	protected $acf_to_local_types;

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Abstract_Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		parent::__construct();

		$this->formatter = $formatter;

		$this->acf_to_local_types = array(
			'text'             => array(
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			'textarea'         => array(
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			'number'           => array(
				'supported_sources' => $this->formatter->get_number_supported_sources(),
			),
			'range'            => array(
				'supported_sources' => $this->formatter->get_number_supported_sources(),
			),
			'email'            => array(
				'supported_sources' => $this->formatter->get_email_supported_sources(),
				'format'            => array( 'strategies' => array( 'format_to_string', 'format_to_email' ) ),
			),
			'url'              => array(
				'supported_sources' => $this->formatter->get_url_supported_sources(),
				'format'            => array( 'strategies' => array( 'format_to_string', 'acf_format_to_url' ) ),
			),
			'password'         => array(
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			'image'            => array(
				'supported_sources' => $this->formatter->get_files_supported_sources(),
				'format'            => array( 'strategies' => array( 'format_to_one_array_length', 'format_attachments_to_attachments_id', 'format_attachments_id_to_images_id', 'format_to_first_array_item' ) ),
			),
			'gallery'          => array(
				'supported_sources' => array_merge( $this->formatter->get_files_supported_sources(), $this->formatter->prefix_proxy_fields_types( $this->formatter->get_files_supported_sources() ) ),
				'format'            => array( 'strategies' => array( 'format_attachments_to_attachments_id', 'format_attachments_id_to_images_id' ) ),
			),
			'file'             => array(
				'supported_sources' => $this->formatter->get_files_supported_sources(),
				'format'            => array(
					'strategies' => array( 'format_to_one_array_length', 'format_attachments_to_attachments_id', 'format_to_first_array_item' ),
				),
			),
			'wysiwyg'          => array(
				'supported_sources' => $this->formatter->get_string_supported_sources(),
			),
			'select'           => array(
				'supported_sources' => $this->formatter->get_single_select_supported_sources(),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( isset( $field['multiple'] ) && $field['multiple'] ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->get_multiple_select_supported_sources() );
					}
					return $acf_type;
				},
			),
			'checkbox'         => array(
				'supported_sources' => $this->formatter->get_multiple_select_supported_sources(),
			),
			'radio'            => array(
				'supported_sources' => $this->formatter->get_single_select_supported_sources(),
			),
			'button_group'     => array(
				'supported_sources' => $this->formatter->get_single_select_supported_sources(),
			),
			'true_false'       => array(
				'supported_sources' => $this->formatter->get_boolean_supported_sources(),
			),

			'link'             => array(
				'sub_fields' => array(
					'title' => array(
						'label'             => __( 'Title', 'air-wp-sync-pro-plus' ),
						'supported_sources' => $this->formatter->get_string_supported_sources(),
					),
					'url'   => array(
						'label'             => __( 'URL', 'air-wp-sync-pro-plus' ),
						'supported_sources' => $this->formatter->get_url_supported_sources(),
						'format'            => array( 'strategies' => array( 'acf_format_to_url' ) ),
					),
				),
			),

			'google_map'       => array(
				'sub_fields' => array(
					'address' => array(
						'label'             => __( 'Address', 'air-wp-sync-pro-plus' ),
						'supported_sources' => array_merge( $this->formatter->get_single_line_text_supported_sources(), $this->formatter->get_multiple_lines_text_supported_sources() ),
					),
					'lat'     => array(
						'label'             => __( 'Latitude', 'air-wp-sync-pro-plus' ),
						'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
					),
					'lng'     => array(
						'label'             => __( 'Longitude', 'air-wp-sync-pro-plus' ),
						'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
					),
				),
			),

			'date_picker'      => array(
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
				'format'            => array(
					'strategies'  => array( 'format_to_datetime' ),
					'post_format' => function ( $value ) {
						if ( $value instanceof \DateTimeInterface ) {
							$value = $value->format( 'Ymd' );
						} else {
							$value = '';
						}
						return $value;
					},
				),
			),

			'date_time_picker' => array(
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
				'format'            => array(
					'strategies'  => array( 'format_to_datetime' ),
					'post_format' => function ( $value ) {
						if ( $value instanceof \DateTimeInterface ) {
							$value = $value->format( 'Y-m-d H:i:s' );
						} else {
							$value = '';
						}
						return $value;
					},
				),
			),

			'time_picker'      => array(
				'supported_sources' => $this->formatter->get_datetime_supported_sources(),
				'format'            => array(
					'strategies'  => array( 'format_to_datetime' ),
					'post_format' => function ( $value ) {
						if ( $value instanceof \DateTimeInterface ) {
							$value = $value->format( 'H:i:s' );
						} else {
							$value = '';
						}
						return $value;
					},
				),
			),

			'page_link'        => array(
				'supported_sources' => $this->formatter->get_url_supported_sources(),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( isset( $field['multiple'] ) && $field['multiple'] ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->prefix_proxy_fields_types( $acf_type['supported_sources'] ) );
					}
					return $acf_type;
				},
				'format'            => array(
					'strategies'  => array( 'format_to_array', 'map:acf_format_to_url' ),
					'post_format' => function ( $values ) {
						foreach ( $values as &$field_value ) {
							if ( $field_value ) {
								$post_id = url_to_postid( $field_value );
								if ( 0 === $post_id ) {
									$field_value = '';
								}
							}
						}
						return $values;
					},
				),
			),

			'user'             => array(
				'supported_sources' => $this->formatter->get_email_supported_sources(),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( isset( $field['multiple'] ) && $field['multiple'] ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->prefix_proxy_fields_types( $acf_type['supported_sources'] ) );
					}
					return $acf_type;
				},
				'format'            => array(
					'strategies'  => array( 'format_to_array', 'map:format_email_to_user_id' ),
					'post_format' => function ( $values ) {
						return array_values( array_filter( $values ) );
					},
				),
			),

			'post_object'      => array(
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( isset( $field['multiple'] ) && $field['multiple'] ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->get_multiple_select_supported_sources() );
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->prefix_proxy_fields_types( $acf_type['supported_sources'] ) );
					}
					return $acf_type;
				},
				'format'            => array(
					'strategies'  => array( 'format_to_array', 'map:format_path_to_url', 'map:format_url_to_post_id' ),
					'post_format' => function ( $values ) {
						return array_values( array_filter( $values ) );
					},
				),
			),

			'relationship'     => array(
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( ! $field['max'] || $field['max'] > 1 ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->get_multiple_select_supported_sources() );
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->prefix_proxy_fields_types( $acf_type['supported_sources'] ) );
					}
					return $acf_type;
				},
				'format'            => array(
					'strategies'  => array( 'format_to_array', 'map:format_path_to_url', 'map:format_url_to_post_id' ),
					'post_format' => function ( $values ) {
						return array_values( array_filter( $values ) );
					},
				),
			),

			'color_picker'     => array(
				'supported_sources' => $this->formatter->get_single_line_text_supported_sources(),
			),

			'taxonomy'         => array(
				'supported_sources' => array_merge(
					$this->formatter->get_single_line_text_supported_sources(),
					$this->formatter->get_single_select_supported_sources(),
					$this->formatter->get_multiple_select_supported_sources()
				),
				'extra_filter'      => function ( $acf_type, $field ) {
					if ( isset( $field['field_type'] ) && in_array( $field['field_type'], array( 'checkbox', 'multi_select' ), true ) ) {
						$acf_type['supported_sources'] = array_merge( $acf_type['supported_sources'], $this->formatter->prefix_proxy_fields_types( $acf_type['supported_sources'] ) );
					}
					return $acf_type;
				},
				'form_options'      => array(
					array(
						'name'  => 'split_comma_separated_string_into_terms',
						'type'  => 'checkbox',
						'label' => __( 'Split comma-separated string into terms', 'air-wp-sync-pro-plus' ),
					),
				),
				'format'            => array(
					'pre_format' => function ( $value, $context ) {
						$wp_field_mapping = $context->get_destination_field_mapping();
						$options = $context->get_destination_options();
						$context->set_args(
							array(
								'taxonomy' => $wp_field_mapping['acf_field']['taxonomy'],
								'split_comma_separated_string_into_terms' => ! empty( $options['form_options_values']['split_comma_separated_string_into_terms'] ),
							)
						);
						return $value;
					},
					'strategies' => array( 'format_names_to_terms_id' ),
				),
			),
		);

		add_filter( 'airwpsync/acf/get-field-conf', array( $this, 'generate_repeater_conf' ), 10, 2 );

		add_action( 'airwpsync/import_record_after', array( $this, 'add_acf_fields' ), 10, 4 );
	}

	/**
	 * Generate repeater field conf on the fly.
	 *
	 * @param array $field_conf Field conf as defined acf_to_local_types.
	 * @param array $field ACF field.
	 *
	 * @return mixed
	 */
	public function generate_repeater_conf( $field_conf, $field ) {
		if ( strpos( $field['type'], 'repeater' ) !== 0 ) {
			return $field_conf;
		}
		if ( strpos( $field['type'], '.' ) === false ) {
			// Dynamically build subfields for the repeater.
			$field_conf['sub_fields'] = array();
			foreach ( $field['sub_fields'] as $sub_field ) {
				$acf_field_conf = $this->get_acf_field_conf( $sub_field );
				if ( 'repeater' === $sub_field['type'] ) {
					// Display a warning, we don't support nested repeater. Make sure we display the message once.
					remove_action( 'airwpsync/metabox/after_mapping', __NAMESPACE__ . '\Abstract_ACF_Destination::display_repeater_warning' );
					add_action( 'airwpsync/metabox/after_mapping', __NAMESPACE__ . '\Abstract_ACF_Destination::display_repeater_warning' );
					continue;
				}
				if ( $acf_field_conf ) {
					$sub_field_conf = $this->get_acf_field_conf( $sub_field );
					if ( ! $sub_field_conf ) {
						continue;
					}
					$sub_field_conf          = $this->override_format_and_supported_sources( $sub_field_conf );
					$sub_field_conf['label'] = $sub_field['label'];
					if ( isset( $sub_field_conf['sub_fields'] ) ) {
						foreach ( $sub_field_conf['sub_fields'] as &$sub_sub_field ) {
							$sub_sub_field = $this->override_format_and_supported_sources( $sub_sub_field );
						}
					}
					$field_conf['sub_fields'][ $sub_field['key'] ] = $sub_field_conf;
				}
			}
		} else {
			$key_parts = explode( '.', $field['key'] );

			$field_conf = $this->get_acf_field_conf( get_field_object( $key_parts[0] ) );
			array_shift( $key_parts );
			$count_key_parts = count( $key_parts );
			while ( $count_key_parts > 0 ) {
				$key        = array_shift( $key_parts );
				$field_conf = $field_conf['sub_fields'][ $key ];
				--$count_key_parts;
			}
		}
		return $field_conf;
	}

	/**
	 * Override format and supported_value_types to manage Link to another table field.
	 *
	 * @param array $field_conf Field conf.
	 *
	 * @return array
	 */
	protected function override_format_and_supported_sources( $field_conf ) {
		// If there is a format on the subfield apply this format to all Remote Fields.
		if ( isset( $field_conf['format'] ) ) {
			$format = $field_conf['format'];
			// Field format should be applied on all fields values.
			$field_conf['format'] = array(
				'pre_format'  => function ( $value, $field_context ) use ( $format ) {
					if ( isset( $format['pre_format'] ) ) {
						$pre_format_fn = $format['pre_format'];
						return array_map(
							function ( $sub_value ) use ( $pre_format_fn, $field_context ) {
								$rf = new \ReflectionFunction( $pre_format_fn );
								if ( $rf->getNumberOfRequiredParameters() === 1 ) {
									$sub_value = $pre_format_fn( $sub_value );
								} elseif ( $rf->getNumberOfRequiredParameters() === 2 ) {
									$sub_value = $pre_format_fn( $sub_value, $field_context );
								}
								return $sub_value;
							},
							$value
						);
					}
					return $value;
				},
				'strategies'  => array_map(
					function ( $strategy ) {
						return 'map:' . $strategy;
					},
					$format['strategies']
				),
				'post_format' => function ( $value ) use ( $format ) {
					if ( isset( $format['post_format'] ) ) {
						$post_format_fn = $format['post_format'];
						return array_map(
							function ( $sub_value ) use ( $post_format_fn ) {
								return $post_format_fn( $sub_value );
							},
							$value
						);
					}
					return $value;
				},
			);
		} else {
			// Set default format function to return a String[].
			$field_conf['format'] = array( 'strategies' => array( 'map:format_to_string' ) );
		}

		if ( isset( $field_conf['supported_sources'] ) ) {
			// Override supported_value_types.
			$field_conf['supported_sources'] = $this->formatter->prefix_proxy_fields_types( $field_conf['supported_sources'] );
		}
		return $field_conf;
	}

	/**
	 * Reformat composed fields for repeater.
	 * The value is formatted by subfield: [ 'repeater_field_key' => [ 'sub_field_key' => [ value1, value2 ] ] ]
	 * Reformat like: [ 'repeater_field_key' => [ [ 'sub_field_key' => value1 ], [ 'sub_field_key' => value2 ] ]
	 *
	 * @param array $composed_fields Composed fields as built by `add_acf_fields` method.
	 *
	 * @return array
	 */
	public function reformat_repeater_composed_fields( $composed_fields ) {

		foreach ( $composed_fields as $composed_field_key => $composed_field ) {
			$acf_field = get_field_object( $composed_field_key );
			if ( 'repeater' === $acf_field['type'] ) {
				$new_value = array();
				foreach ( $composed_field as $composed_sub_field_key => $values ) {
					foreach ( $values as $index => $value ) {
						if ( is_numeric( $index ) ) {
							if ( ! isset( $new_value[ $index ] ) ) {
								$new_value[] = array();
							}
							$new_value[ $index ][ $composed_sub_field_key ] = $value;
						} else {
							// We need to go deeper... (ex link -> url).
							foreach ( $value as $sub_index => $sub_value ) {
								$new_value[ $sub_index ][ $composed_sub_field_key ][ $index ] = $sub_value;
							}
						}
					}
				}
				$composed_fields[ $composed_field_key ] = $new_value;
			}
		}
		return $composed_fields;
	}

	/**
	 * Display warning about repeaters, we don't support nested repeater yet.
	 *
	 * @return void
	 */
	public static function display_repeater_warning() {
		$message = __( 'Please note that we don\'t support nested repeater yet.', 'air-wp-sync-pro-plus' );
		echo '<div class="notice notice-warning inline" style="margin: 20px;"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Get field config from the "acf_to_local_types" property.
	 * The field type could be a type from ACF or a composed one like "link.url".
	 *
	 * @param array $field The ACF field.
	 *
	 * @return array
	 */
	protected function get_acf_field_conf( $field ) {
		$confs = $this->acf_to_local_types;
		$type  = $field['type'];
		if ( strpos( $type, '.' ) !== false ) {
			$type_parts = explode( '.', $type );
			$type       = array_pop( $type_parts );
			foreach ( $type_parts as $sub_type ) {
				if ( isset( $confs[ $sub_type ]['sub_fields'] ) ) {
					$confs = $confs[ $sub_type ]['sub_fields'];
				}
			}
		}
		$acf_field_conf = $confs[ $type ] ?? array();

		if ( isset( $acf_field_conf['extra_filter'] ) ) {
			$acf_field_conf = $acf_field_conf['extra_filter']( $acf_field_conf, $field );
		}
		return apply_filters( 'airwpsync/acf/get-field-conf', $acf_field_conf, $field );
	}

	/**
	 * Handle ACF field importing.
	 *
	 * @param Abstract_Importer $importer Importer.
	 * @param array             $fields Fields.
	 * @param \stdClass         $record The Remote object.
	 * @param int               $post_id The post id.
	 */
	public function add_acf_fields( $importer, $fields, $record, $post_id ) {

		// Prepare $custom_format_strategies based on 'format' props defined in self::acf_to_local_types.
		$custom_format_strategies = array();
		$mapped_fields            = $this->get_destination_mapping( $importer );

		foreach ( $mapped_fields as $mapped_field ) {
			$destination      = $mapped_field['wordpress'];
			$wp_field_mapping = $this->get_field_mapping( $destination );
			$acf_field_conf   = $this->get_acf_field_conf( $wp_field_mapping['acf_field'] );
			if ( isset( $acf_field_conf['format'] ) ) {
				$custom_format_strategies[ $destination ] = $acf_field_conf['format'];
			}
		}
		$context = new Import_Context();
		$context
			->set_importer( $importer )
			->set_existing_object_id( $post_id );

		$acf_fields_values = $this->format_data( array(), $context, $fields, $custom_format_strategies );
		$composed_fields   = array();

		foreach ( $acf_fields_values as $key => $value ) {
			$key_parts = explode( '.', $key );

			// Remove group from key.
			array_shift( $key_parts );
			$key = implode( '.', $key_parts );

			// if it's a composed field add it to the $composed_fields variable.
			// e.g link.url -> $composed_fields['link']['url'].
			if ( count( $key_parts ) > 1 ) {
				if ( ! isset( $composed_fields[ $key_parts[0] ] ) ) {
					$composed_fields[ $key_parts[0] ] = array();
				}
				// At some point the code below should be recursive (e.g manage sub repeaters).
				if ( isset( $key_parts[2] ) ) {
					if ( ! isset( $composed_fields[ $key_parts[0] ][ $key_parts[1] ] ) ) {
						$composed_fields[ $key_parts[0] ][ $key_parts[1] ] = array();
					}
					$composed_fields[ $key_parts[0] ][ $key_parts[1] ][ $key_parts[2] ] = $value;
				} else {
					$composed_fields[ $key_parts[0] ][ $key_parts[1] ] = $value;
				}

				continue;
			}
			$this->update_field( $key, $value, $post_id );
		}

		$composed_fields = $this->reformat_repeater_composed_fields( $composed_fields );

		// All composed fields have been regrouped, we can now import them.
		foreach ( $composed_fields as $composed_field_key => $composed_field_values ) {
			$current_value = get_field( $composed_field_key, $post_id );
			$new_value     = $composed_field_values;
			// Merge only if the last value is not an indexed array.
			if ( is_array( $current_value ) && is_array( $composed_field_values ) && ! isset( $composed_field_values[0] ) ) {
				// Merge current value and new value so if some subfields are unmapped there are not lost.
				$new_value = array_merge( $current_value, $composed_field_values );
			}
			$this->update_field( $composed_field_key, $new_value, $post_id );
		}
	}

	/**
	 * Update ACF field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Field valye.
	 * @param int    $destination_id WordPress destination object id.
	 *
	 * @return void
	 */
	abstract public function update_field( $key, $value, $destination_id );


	/**
	 * Retrieve ACF fields. Optionally filter them with the $filters param (@see acf_get_field_groups).
	 *
	 * @param array $filters Filters passed to `acf_get_field_groups`.
	 *
	 * @return array
	 */
	protected function get_acf_fields( $filters = array() ) {
		$all_fields = array();
		$groups     = $this->acf_get_field_groups( $filters );

		foreach ( $groups as $group ) {
			$fields     = acf_get_fields( $group['key'] );
			$fields     = array_reduce(
				$fields,
				function ( $result, $field ) use ( $group ) {
					$result = $this->flatten_conf( $result, $group, $field );

					return $result;
				},
				array()
			);
			$all_fields = array_merge( $all_fields, $fields );
		}

		return $all_fields;
	}

	/**
	 * Returned filtered ACF fields groups.
	 *
	 * @param array $filters Filters.
	 *
	 * @return array
	 */
	protected function acf_get_field_groups( $filters = array() ) {
		return acf_get_field_groups( $filters );
	}

	/**
	 * Flatten config array.
	 *
	 * @param array $result Carry.
	 * @param array $group ACF Field group config.
	 * @param array $field ACF Field config.
	 *
	 * @return array
	 */
	protected function flatten_conf( $result, $group, $field ) {
		$acf_field_conf = $this->get_acf_field_conf( $field );

		$field['group'] = $group;
		if ( isset( $field['_clone'] ) ) {
			$field['key'] = $field['__key'];
		}
		if ( isset( $acf_field_conf['sub_fields'] ) ) {
			$result = $this->flatten_sub_fields_conf( $result, $acf_field_conf['sub_fields'], $field );
		} else {
			$result[] = $field;
		}

		return $result;
	}

	/**
	 * Flatten sub fields config array.
	 *
	 * @param array $result Carry.
	 * @param array $sub_fields_conf Sub fields config array.
	 * @param array $field ACF Field config.
	 * @param array $sub_fields_labels List of sub fields labels.
	 *
	 * @return array
	 */
	protected function flatten_sub_fields_conf( $result, $sub_fields_conf, $field, $sub_fields_labels = array() ) {
		foreach ( $sub_fields_conf as $sub_field_key => $sub_field ) {
			$sub_sub_fields_labels = array_merge( $sub_fields_labels, array( $sub_field['label'] ) );
			$field_edited          = array_merge(
				array(),
				$field,
				array(
					'type' => $field['type'] . '.' . $sub_field_key,
					'key'  => $field['key'] . '.' . $sub_field_key,
				)
			);
			if ( isset( $sub_field['sub_fields'] ) ) {
				$result = $this->flatten_sub_fields_conf( $result, $sub_field['sub_fields'], $field_edited, $sub_sub_fields_labels );
			} else {
				$field_edited['label'] = $field_edited['label'] . ' (' . implode( ' > ', $sub_sub_fields_labels ) . ')';
				$result[]              = $field_edited;
			}
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_group() {
		return array(
			'label' => __( 'ACF', 'air-wp-sync-pro-plus' ),
			'slug'  => 'acf',
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	protected function get_mapping_fields() {
		$fields = $this->get_acf_fields();

		return array_map(
			function ( $field ) {
				$acf_field_conf = $this->get_acf_field_conf( $field );
				return array(
					'value'             => $field['group']['key'] . '.' . $field['key'],
					'label'             => $field['group']['title'] . ' / ' . $field['label'],
					'enabled'           => true,
					'supported_sources' => $acf_field_conf['supported_sources'] ?? array(),
					'acf_type'          => $field['type'],
					'acf_field'         => $field,
					'form_options'      => $acf_field_conf['form_options'] ?? array(),
				);
			},
			$fields
		);
	}
}
