<?php

use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Fns;
use function WPML\FP\pipe;

/**
 * Class WPML_Elementor_Translate_IDs
 */
class WPML_Elementor_Translate_IDs implements IWPML_Action {

	/** @var \WPML\Utils\DebugBackTrace */
	private $debug_backtrace;

	/**
	 * WPML_Elementor_Translate_IDs constructor.
	 *
	 * @param \WPML\Utils\DebugBackTrace $debug_backtrace
	 */
	public function __construct( \WPML\Utils\DebugBackTrace $debug_backtrace ) {
		$this->debug_backtrace = $debug_backtrace;
	}

	public function add_hooks() {
		add_filter( 'elementor/theme/get_location_templates/template_id', [ $this, 'translate_theme_location_template_id' ] );
		add_filter( 'elementor/theme/get_location_templates/condition_sub_id', [ $this, 'translate_location_condition_sub_id' ], 10, 2 );
		add_filter( 'elementor/documents/get/post_id', [ $this, 'translate_template_id' ] );
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'translate_global_widget_ids' ], 10, 2 );
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'translate_product_ids' ], 10, 2 );
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'translate_ids_in_widget_fields' ], 10, 2 );
	}

	public function translate_theme_location_template_id( $template_id ) {
		return $this->translate_id( $template_id );
	}

	/**
	 * @param int|string $sub_id
	 * @param array      $parsed_condition
	 *
	 * @return int|string
	 */
	public function translate_location_condition_sub_id( $sub_id, $parsed_condition ) {
		/**
		 * `$sub_name` gives a context for the `$sub_id`, it can be either:
		 * - `child_of`
		 * - `in_{taxonomy}`
		 * - `in_{taxonomy}_children`
		 * - `{post_type}`
		 * - `{taxonomy}`
		 */
		$sub_name = isset( $parsed_condition['sub_name'] ) ? $parsed_condition['sub_name'] : null;

		if ( (int) $sub_id > 0 && $sub_name ) {
			$startsWith = Str::startsWith( Fns::__, $sub_name );
			$getType    = Str::pregReplace( Fns::__, '', $sub_name );

			$findReplace = wpml_collect(
				[
					'in_'           => '/^in_|_children$/',
					'child_of_'     => '/^child_of_/',
					'any_child_of_' => '/^any_child_of_/',
				]
			);

			if ( 'child_of' === $sub_name ) {
				$element_type = get_post_type( $sub_id );
			} else {
				$element_type = $findReplace
					->filter( pipe( Fns::nthArg( 1 ), $startsWith ) )
					->map( $getType )
					->first( Fns::identity(), $sub_name );
			}

			$sub_id = $this->translate_id( $sub_id, $element_type );
		}

		return $sub_id;
	}

	/**
	 * @param int|string $template_id
	 *
	 * @return int|string
	 */
	public function translate_template_id( $template_id ) {
		if ( $this->should_translate_template() ) {
			$template_id = $this->translate_id( $template_id );
		}

		return $template_id;
	}

	/**
	 * @return bool
	 */
	private function should_translate_template() {
		return $this->is_WP_widget_call()
		|| $this->is_shortcode_call()
		|| $this->is_template_widget_call()
		|| $this->is_form_ajax_handler_call();
	}

	/**
	 * @return bool
	 */
	private function is_WP_widget_call() {
		return $this->debug_backtrace->is_class_function_in_call_stack(
			'ElementorPro\Modules\Library\WP_Widgets\Elementor_Library',
			'widget'
		);
	}

	/**
	 * @return bool
	 */
	private function is_shortcode_call() {
		return $this->debug_backtrace->is_class_function_in_call_stack(
			'ElementorPro\Modules\Library\Classes\Shortcode',
			'shortcode'
		);
	}

	/**
	 * @return bool
	 */
	private function is_template_widget_call() {
		return $this->debug_backtrace->is_class_function_in_call_stack(
			'ElementorPro\Modules\Library\Widgets\Template',
			'render'
		);
	}

	/**
	 * @return bool
	 */
	private function is_form_ajax_handler_call() {
		return $this->debug_backtrace->is_class_function_in_call_stack(
			'ElementorPro\Modules\Forms\Classes\Ajax_Handler',
			'ajax_send_form'
		);
	}

	/**
	 * @param array $data_array
	 * @param int   $post_id
	 *
	 * @return array The modified data array with translated IDs.
	 */
	public function translate_global_widget_ids( $data_array, $post_id ) {
		foreach ( $data_array as &$data ) {
			if ( isset( $data['elType'] ) && 'widget' === $data['elType'] ) {
				if ( 'global' === $data['widgetType'] ) {
					$data['templateID'] = $this->translate_id( $data['templateID'] );
				} elseif ( 'template' === $data['widgetType'] ) {
					$data['settings']['template_id'] = $this->translate_id( $data['settings']['template_id'] );
				}
			}
			$data['elements'] = $this->translate_global_widget_ids( $data['elements'], $post_id );
		}

		return $data_array;
	}

	/**
	 * @param array $data_array
	 * @param int   $post_id
	 *
	 * @return array
	 */
	public function translate_product_ids( $data_array, $post_id ) {
		foreach ( $data_array as &$data ) {
			if (
				Obj::prop( 'elType', $data ) === 'widget'
				&& Obj::prop( 'widgetType', $data ) === 'wc-add-to-cart'
				&& Obj::propOr( false, 'product_id', $data['settings'] )
			) {
				$data['settings']['product_id'] = $this->translate_id( $data['settings']['product_id'] );
			}

			$data['elements'] = $this->translate_product_ids( $data['elements'], $post_id );
		}

		return $data_array;
	}

	/**
	 * @param int|string $element_id
	 * @param string     $element_type
	 *
	 * @return int
	 */
	private function translate_id( $element_id, $element_type = null ) {
		if ( ! $element_type || 'any_child_of' === $element_type ) {
			$element_type = get_post_type( $element_id );
		}

		$translated_id = apply_filters( 'wpml_object_id', $element_id, $element_type, true );

		if ( is_string( $element_id ) ) {
			$translated_id = (string) $translated_id;
		}

		return $translated_id;
	}

	/**
	 * @param array $data_array
	 * @param int   $post_id
	 *
	 * @return array
	 */
	public function translate_ids_in_widget_fields( $data_array, $post_id ) {
		foreach ( $data_array as &$data ) {
			if ( isset( $data['elType'] ) && 'widget' === $data['elType'] ) {
				$data = $this->translate_widget_ids( $data );
			}

			if ( ! empty( $data['elements'] ) ) {
				$data['elements'] = $this->translate_ids_in_widget_fields( $data['elements'], $post_id );
			}
		}

		return $data_array;
	}

	/**
	 * @return array
	 */
	private function get_widget_fields_with_ids() {
		/**
		 * Filters the configuration array for fields containing IDs that need translation in Elementor widgets.
		 *
		 * @since 2.3.0
		 *
		 * @param array<
		 *     string, array{
		 *         fields: array<
		 *             array{
		 *                 field_key: string,
		 *                 repeater_key?: string,
		 *                 id_type?: "term",
		 *                 type?: string
		 *             }
		 *         >
		 *     }
		 * > $fields_with_ids Configuration array where:
		 *   - key is the widget name (string).
		 *   - value is an array containing:
		 *     - fields: array of field configurations:
		 *       - field_key: (required) The name of the field containing the ID(s).
		 *       - repeater_key: (optional) If set, indicates this is a repeater field and contains the repeater field name.
		 *       - id_type: (optional) Set to "term" for taxonomy term IDs. Defaults to post IDs if not set.
		 *       - type: (optional) You can set the specific post-type slug or taxonomy slug for a slight improvement in performance.
		 */
		return apply_filters( 'wpmlpb_elementor_fields_with_ids', [] );
	}

	/**
	 * @param array $data
	 *
	 * @return array Modified data with translated IDs
	 */
	private function translate_widget_ids( $data ) {
		$widget_name     = $data['widgetType'] ?? null;
		$fields_with_ids = $this->get_widget_fields_with_ids();

		if ( isset( $fields_with_ids[ $widget_name ] ) ) {
			foreach ( $fields_with_ids[ $widget_name ]['fields'] as $field_config ) {
				if ( isset( $field_config['repeater_key'] ) ) {
					$data['settings'] = $this->translate_repeater_field_ids( $data['settings'], $field_config );
				} else {
					$data['settings'] = $this->translate_field_ids( $data['settings'], $field_config );
				}
			}
		}

		return $data;
	}

	/**
	 * @param array $settings
	 * @param array $field_config
	 *
	 * @return array Modified settings with translated IDs
	 */
	private function translate_repeater_field_ids( $settings, $field_config ) {
		$repeater_key = $field_config['repeater_key'];
		$field_key    = $field_config['field_key'];

		if ( empty( $settings[ $repeater_key ] ) || ! is_array( $settings[ $repeater_key ] ) ) {
			return $settings;
		}

		foreach ( $settings[ $repeater_key ] as &$value ) {
			if ( ! empty( $value[ $field_key ] ) && is_numeric( $value[ $field_key ] ) ) {
				$type                = $field_config['type'] ?? null;
				$value[ $field_key ] = $this->translate_id( $value[ $field_key ], $type );
			}
		}

		return $settings;
	}

	/**
	 * @param array $settings
	 * @param array $field_config
	 *
	 * @return array Modified settings with translated IDs
	 */
	private function translate_field_ids( $settings, $field_config ) {
		$field_path = explode( '>', $field_config['field_key'] );
		$current    = &$settings;

		foreach ( $field_path as $key ) {
			if ( ! isset( $current[ $key ] ) ) {
				return $settings;
			}
			$current = &$current[ $key ];
		}

		if ( is_array( $current ) ) {
			$current = $this->translate_ids_from_array( $current, $field_config );
		} elseif ( is_numeric( $current ) ) {
			$type    = $field_config['type'] ?? null;
			$current = $this->translate_id( $current, $type );
		}

		return $settings;
	}

	/**
	 * @param array $ids
	 * @param array $field_config
	 *
	 * @return array
	 */
	private function translate_ids_from_array( $ids, $field_config ) {
		$translated_ids = [];

		foreach ( $ids as $id ) {
			if ( ! is_numeric( $id ) ) {
				continue;
			}

			$type = $this->get_element_type( $id, $field_config );
			if ( $type ) {
				$translated_ids[] = $this->translate_id( $id, $type );
			}
		}

		return $translated_ids;
	}

	/**
	 * @param int|string $id
	 * @param array      $field_config
	 *
	 * @return string|null
	 */
	private function get_element_type( $id, $field_config ) {
		if ( isset( $field_config['id_type'] ) && 'term' === $field_config['id_type'] ) {
			$term = get_term( $id );
			return $term instanceof \WP_Term ? $term->taxonomy : null;
		}
		return get_post_type( $id );
	}

}
