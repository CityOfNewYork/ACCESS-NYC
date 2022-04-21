<?php
/**
 * WPML_Beaver_Builder_Translatable_Nodes class file.
 *
 * @package wpml-page-builders-beaver-builder
 */

use WPML\PB\BeaverBuilder\Modules\ModuleWithItemsFromConfig;

/**
 * Class WPML_Beaver_Builder_Translatable_Nodes
 */
class WPML_Beaver_Builder_Translatable_Nodes implements IWPML_Page_Builders_Translatable_Nodes {

	/**
	 * Nodes to translate.
	 *
	 * @var array
	 */
	private $nodes_to_translate;

	/**
	 * Get translatable node.
	 *
	 * @param string|int $node_id  Node id.
	 * @param stdClass   $settings Node settings.
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $settings ) {
		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		$strings = array();

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_key = $field['field'];
					if ( isset( $settings->$field_key ) && trim( $settings->$field_key ) ) {

						$string = new WPML_PB_String(
							$settings->$field_key,
							$this->get_string_name( $node_id, $field, $settings ),
							$field['type'],
							$field['editor_type'],
							$this->get_wrap_tag( $settings )
						);

						$strings[] = $string;
					}
				}

				foreach ( $this->get_integration_instances( $node_data ) as $node ) {
					try {
						$strings = $node->get( $node_id, $settings, $strings );
						// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					} catch ( Exception $e ) {}
					// phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}
		}

		return $strings;
	}

	/**
	 * Update translatable node.
	 *
	 * @param string         $node_id  Node id.
	 * @param stdClass       $settings Node settings.
	 * @param WPML_PB_String $string   String object.
	 *
	 * @return stdClass
	 */
	public function update( $node_id, $settings, WPML_PB_String $string ) {
		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_key = $field['field'];
					if ( $this->get_string_name( $node_id, $field, $settings ) === $string->get_name() ) {
						$settings->$field_key = $string->get_value();
					}
				}

				foreach ( $this->get_integration_instances( $node_data ) as $node ) {
					try {
						$node->update( $node_id, $settings, $string );
						// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					} catch ( Exception $e ) {}
					// phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}
		}

		return $settings;
	}

	/**
	 * @param array $node_data
	 *
	 * @return WPML_Beaver_Builder_Module_With_Items[]
	 */
	private function get_integration_instances( array $node_data ) {
		$instances = [];

		if ( isset( $node_data['integration-class'] ) ) {
			try {
				$instances[] = new $node_data['integration-class']();
				// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( Exception $e ) {}
			// phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		if ( isset( $node_data['fields_in_item'] ) ) {
			foreach ( $node_data['fields_in_item'] as $item_of => $config ) {
				$instances[] = new ModuleWithItemsFromConfig( $item_of, $config );
			}
		}

		return array_filter( $instances );
	}

	/**
	 * Get string name.
	 *
	 * @param string   $node_id  Node id.
	 * @param array    $field    Page builder field.
	 * @param stdClass $settings Node settings.
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $field['field'] . '-' . $settings->type . '-' . $node_id;
	}

	/**
	 * Get wrap tag for string.
	 * Used for SEO, can contain (h1...h6, etc.)
	 *
	 * @param stdClass $settings Field settings.
	 *
	 * @return string
	 */
	private function get_wrap_tag( $settings ) {
		if ( isset( $settings->type ) && 'heading' === $settings->type && isset( $settings->tag ) ) {
				return $settings->tag;
		}

		return '';
	}

	/**
	 * Check if node condition is ok.
	 *
	 * @param array    $node_data Node data.
	 * @param stdClass $settings  Node settings.
	 *
	 * @return bool
	 */
	private function conditions_ok( $node_data, $settings ) {
		$conditions_meet = true;
		foreach ( $node_data['conditions'] as $field_key => $field_value ) {
			if ( ! isset( $settings->$field_key ) || $settings->$field_key !== $field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	/**
	 * @return array
	 */
	public static function get_nodes_to_translate() {
		return array(
			'button'         => array(
				'conditions' => array( 'type' => 'button' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Button: Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Button: Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'heading'        => array(
				'conditions' => array( 'type' => 'heading' ),
				'fields'     => array(
					array(
						'field'       => 'heading',
						'type'        => __( 'Heading', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Heading: Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'html'           => array(
				'conditions' => array( 'type' => 'html' ),
				'fields'     => array(
					array(
						'field'       => 'html',
						'type'        => __( 'HTML', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
				),
			),
			'photo'          => array(
				'conditions' => array( 'type' => 'photo' ),
				'fields'     => array(
					array(
						'field'       => 'link_url',
						'type'        => __( 'Photo: Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'rich-text'      => array(
				'conditions' => array( 'type' => 'rich-text' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Text Editor', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
				),
			),
			'accordion'      => array(
				'conditions'        => array( 'type' => 'accordion' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Accordion',
			),
			'pricing-table'  => array(
				'conditions'        => array( 'type' => 'pricing-table' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Pricing_Table',
			),
			'tabs'           => array(
				'conditions'        => array( 'type' => 'tabs' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Tab',
			),
			'callout'        => array(
				'conditions' => array( 'type' => 'callout' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Callout: Heading', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'text',
						'type'        => __( 'Callout: Text', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'cta_text',
						'type'        => __( 'Callout: Call to action text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Callout: Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'contact-form'   => array(
				'conditions' => array( 'type' => 'contact-form' ),
				'fields'     => array(
					array(
						'field'       => 'name_placeholder',
						'type'        => __( 'Contact Form: Name Field Placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'subject_placeholder',
						'type'        => __( 'Contact Form: Subject Field Placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'email_placeholder',
						'type'        => __( 'Contact Form: Email Field Placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'phone_placeholder',
						'type'        => __( 'Contact Form: Phone Field Placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'message_placeholder',
						'type'        => __( 'Contact Form: Your Message Placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'terms_checkbox_text',
						'type'        => __( 'Contact Form: Checkbox Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'terms_text',
						'type'        => __( 'Contact Form: Terms and Conditions', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'success_message',
						'type'        => __( 'Contact Form: Success Message', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Contact Form: Button Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'success_url',
						'type'        => __( 'Contact Form: Redirect Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'call-to-action' => array(
				'conditions' => array( 'type' => 'cta' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Call to Action: Heading', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'text',
						'type'        => __( 'Call to Action: Text', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Call to Action: Button text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'btn_link',
						'type'        => __( 'Call to Action: Button link', 'sitepress' ),
						'editor_type' => 'LINK',
					),

				),
			),
			'subscribe-form' => array(
				'conditions' => array( 'type' => 'subscribe-form' ),
				'fields'     => array(
					array(
						'field'       => 'terms_checkbox_text',
						'type'        => __( 'Subscribe form: Checkbox Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'terms_text',
						'type'        => __( 'Subscribe form: Terms and Conditions', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'custom_subject',
						'type'        => __( 'Subscribe form: Notification Subject', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'success_message',
						'type'        => __( 'Subscribe form: Success Message', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Subscribe form: Button Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'success_url',
						'type'        => __( 'Subscribe form: Redirect Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'content-slider' => array(
				'conditions'        => array( 'type' => 'content-slider' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Content_Slider',
			),
			'icon'           => array(
				'conditions' => array( 'type' => 'icon' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Icon: Text', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Icon: Link', 'sitepress' ),
						'editor_type' => 'LINK',
					),
				),
			),
			'icon-group'     => array(
				'conditions'        => array( 'type' => 'icon-group' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Icon_Group',
			),
			'map'            => array(
				'conditions' => array( 'type' => 'map' ),
				'fields'     => array(
					array(
						'field'       => 'address',
						'type'        => __( 'Map: Address', 'sitepress' ),
						'editor_type' => 'LINE',
					),
				),
			),
			'testimonials'   => array(
				'conditions'        => array( 'type' => 'testimonials' ),
				'fields'            => array(
					array(
						'field'       => 'heading',
						'type'        => __( 'Testimonial: Heading', 'sitepress' ),
						'editor_type' => 'LINE',
					),

				),
				'integration-class' => 'WPML_Beaver_Builder_Testimonials',
			),
			'numbers'        => array(
				'conditions' => array( 'type' => 'numbers' ),
				'fields'     => array(
					array(
						'field'       => 'before_number_text',
						'type'        => __( 'Number Counter: Text before number', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'after_number_text',
						'type'        => __( 'Number Counter: Text after number', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'number_prefix',
						'type'        => __( 'Number Counter: Number Prefix', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'number_suffix',
						'type'        => __( 'Number Counter: Number Suffix', 'sitepress' ),
						'editor_type' => 'LINE',
					),
				),
			),
			'post-grid'      => array(
				'conditions' => array( 'type' => 'post-grid' ),
				'fields'     => array(
					array(
						'field'       => 'no_results_message',
						'type'        => __( 'Posts: No Results Message', 'sitepress' ),
						'editor_type' => 'VISUAL',
					),
					array(
						'field'       => 'more_btn_text',
						'type'        => __( 'Posts: Button Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'terms_list_label',
						'type'        => __( 'Posts: Terms Label', 'sitepress' ),
						'editor_type' => 'LINE',
					),
					array(
						'field'       => 'more_link_text',
						'type'        => __( 'Posts: More Link Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
				),
			),
			'post-slider'    => array(
				'conditions' => array( 'type' => 'post-slider' ),
				'fields'     => array(
					array(
						'field'       => 'more_link_text',
						'type'        => __( 'Posts Slider: More Link Text', 'sitepress' ),
						'editor_type' => 'LINE',
					),
				),
			),

		);
	}

	/**
	 * Initialize translatable nodes.
	 */
	public function initialize_nodes_to_translate() {
		$this->nodes_to_translate = apply_filters( 'wpml_beaver_builder_modules_to_translate', self::get_nodes_to_translate() );
	}
}
