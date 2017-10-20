<?php

/**
 * Class WPML_Beaver_Builder_Translatable_Nodes
 */
class WPML_Beaver_Builder_Translatable_Nodes implements IWPML_Page_Builders_Translatable_Nodes {

	/** @var array */
	private $nodes_to_translate;

	/**
	 * @param string|int $node_id
	 * @param obj $settings
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
							$field['editor_type']
						);

						$strings[] = $string;
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node    = new $node_data['integration-class']();
						$strings = $node->get( $node_id, $settings, $strings );
					} catch ( Exception $e ) {
					}
				}
			}
		}

		return $strings;
	}

	/**
	 * @param string $node_id
	 * @param array $settings
	 * @param WPML_PB_String $string
	 *
	 * @return array
	 */
	public function update( $node_id, $settings, WPML_PB_String $string ) {

		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_key = $field['field'];
					if ( $this->get_string_name( $node_id, $field, $settings ) == $string->get_name() ) {
						$settings->$field_key = $string->get_value();
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node = new $node_data['integration-class']();
						$node->update( $node_id, $settings, $string );
					} catch ( Exception $e ) {

					}
				}
			}
		}

		return $settings;
	}

	/**
	 * @param string $node_id
	 * @param array $field
	 * @param mixed $settings
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $field['field'] . '-' . $settings->type . '-' . $node_id;
	}

	/**
	 * @param array $node_data
	 * @param obj $settings
	 *
	 * @return bool
	 */
	private function conditions_ok( $node_data, $settings ) {
		$conditions_meet = true;
		foreach ( $node_data['conditions'] as $field_key => $field_value ) {
			if ( ! isset( $settings->$field_key ) || $settings->$field_key != $field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	public function initialize_nodes_to_translate() {

		$this->nodes_to_translate = array(
			'rich-text'      => array(
				'conditions' => array( 'type' => 'rich-text' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Text Editor', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'html'           => array(
				'conditions' => array( 'type' => 'html' ),
				'fields'     => array(
					array(
						'field'       => 'html',
						'type'        => __( 'HTML', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'button'         => array(
				'conditions' => array( 'type' => 'button' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Button', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'heading'        => array(
				'conditions' => array( 'type' => 'heading' ),
				'fields'     => array(
					array(
						'field'       => 'heading',
						'type'        => __( 'Heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'call to action' => array(
				'conditions' => array( 'type' => 'cta' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Call to Action: Heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'text',
						'type'        => __( 'Call to Action: Text', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Call to Action: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'btn_link',
						'type'        => __( 'Call to Action: Button link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),

				),
			),
			'icon'           => array(
				'conditions' => array( 'type' => 'icon' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Icon text', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'callout'        => array(
				'conditions' => array( 'type' => 'callout' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Callout: Heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'text',
						'type'        => __( 'Callout: Text', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'cta_text',
						'type'        => __( 'Callout: Call to action text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'link',
						'type'        => __( 'Callout: Link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'contact-form'   => array(
				'conditions' => array( 'type' => 'contact-form' ),
				'fields'     => array(
					array(
						'field'       => 'success_message',
						'type'        => __( 'Contact Form: Success message', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Contact Form: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'success_url',
						'type'        => __( 'Contact Form: Redirect link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'numbers'   => array(
				'conditions' => array( 'type' => 'numbers' ),
				'fields'     => array(
					array(
						'field'       => 'before_number_text',
						'type'        => __( 'Number Counter: Text before number', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'after_number_text',
						'type'        => __( 'Number Counter: Text after number', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'accordion'      => array(
				'conditions'        => array( 'type' => 'accordion' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Accordion',
			),
			'tabs'            => array(
				'conditions'        => array( 'type' => 'tabs' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Tab',
			),
			'content-slider'            => array(
				'conditions'        => array( 'type' => 'content-slider' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Content_Slider',
			),
			'testimonials'            => array(
				'conditions'        => array( 'type' => 'testimonials' ),
				'fields'            => array(
					array(
						'field'       => 'heading',
						'type'        => __( 'Testimonial heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),

				),
				'integration-class' => 'WPML_Beaver_Builder_Testimonials',
			),
			'subscribe-form'        => array(
				'conditions' => array( 'type' => 'subscribe-form' ),
				'fields'     => array(
					array(
						'field'       => 'success_message',
						'type'        => __( 'Subscribe form: Success message', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'btn_text',
						'type'        => __( 'Subscribe form: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'success_url',
						'type'        => __( 'Subscribe form: Redirect link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'pricing-table'            => array(
				'conditions'        => array( 'type' => 'pricing-table' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Pricing_Table',
			),
			'icon-group'            => array(
				'conditions'        => array( 'type' => 'icon-group' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Beaver_Builder_Icon_Group',
			),
			'photo'        => array(
				'conditions' => array( 'type' => 'photo' ),
				'fields'     => array(
					array(
						'field'       => 'link_url',
						'type'        => __( 'Link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),

		);

		$this->nodes_to_translate = apply_filters( 'wpml_beaver_builder_modules_to_translate', $this->nodes_to_translate );

	}

}