<?php

/**
 * Class WPML_Elementor_Translatable_Nodes
 */
class WPML_Elementor_Translatable_Nodes implements IWPML_Page_Builders_Translatable_Nodes {

	const SETTINGS_FIELD = 'settings';

	/**
	 * @var string
	 */
	private $settings_field;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	private $nodes_to_translate;

	/**
	 * WPML_Elementor_Translatable_Nodes constructor.
	 */
	public function __construct() {
		$this->settings_field = self::SETTINGS_FIELD;
		$this->type           = 'widgetType';
	}

	/**
	 * @param string|int $node_id
	 * @param array $element
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $element ) {

		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		$strings = array();

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $element ) ) {
				foreach ( $node_data['fields'] as $key => $field ) {
					$field_key = $field['field'];

					if ( is_numeric( $key ) && isset( $element[ $this->settings_field ][ $field_key ] ) && trim( $element[ $this->settings_field ][ $field_key ] ) ) {
						$string    = new WPML_PB_String(
							$element[ $this->settings_field ][ $field_key ],
							$this->get_string_name( $node_id, $field, $element ),
							$field['type'],
							$field['editor_type']
						);
						$strings[] = $string;
					} else if ( isset( $element[ $this->settings_field ][ $key ][ $field_key ] ) && trim( $element[ $this->settings_field ][ $key ][ $field_key ] ) ) {
						$string    = new WPML_PB_String(
							$element[ $this->settings_field ][ $key ][ $field_key ],
							$this->get_string_name( $node_id, $field, $element ),
							$field['type'],
							$field['editor_type']
						);
						$strings[] = $string;
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node    = new $node_data['integration-class']();
						$strings = $node->get( $node_id, $element, $strings );
					} catch ( Exception $e ) {
					}
				}
			}
		}

		return $strings;
	}

	/**
	 * @param string|int $node_id
	 * @param array $element
	 * @param WPML_PB_String $string
	 *
	 * @return array
	 */
	public function update( $node_id, $element, WPML_PB_String $string ) {

		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {

			if ( $this->conditions_ok( $node_data, $element ) ) {
				foreach ( $node_data['fields'] as $key => $field ) {
					$field_key = $field['field'];

					if ( $this->get_string_name( $node_id, $field, $element ) === $string->get_name() ) {
						if ( is_numeric( $key ) ) {
							$element[ $this->settings_field ][ $field_key ] = $string->get_value();
						} else {
							$element[ $this->settings_field ][ $key ][ $field_key ] = $string->get_value();
						}
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node = new $node_data['integration-class']();
						$item = $node->update( $node_id, $element, $string );
						if ( $item ) {
							$element[ $this->settings_field ][ $node->get_items_field() ][ $item['index'] ] = $item;
						}
					} catch ( Exception $e ) {

					}
				}
			}
		}

		return $element;
	}

	/**
	 * @param string $node_id
	 * @param array $field
	 * @param array $settings
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $field['field'] . '-' . $settings[ $this->type ] . '-' . $node_id;
	}

	/**
	 * @param array $node_data
	 * @param array $element
	 *
	 * @return bool
	 */
	private function conditions_ok( $node_data, $element ) {
		$conditions_meet = true;
		foreach ( $node_data['conditions'] as $field_key => $field_value ) {
			if ( ! isset( $element[ $field_key ] ) || $element[ $field_key ] != $field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	public function initialize_nodes_to_translate() {

		$this->nodes_to_translate = array(
			'heading'     => array(
				'conditions' => array( $this->type => 'heading' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'text-editor' => array(
				'conditions' => array( $this->type => 'text-editor' ),
				'fields'     => array(
					array(
						'field'       => 'editor',
						'type'        => __( 'Text editor', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'icon'        => array(
				'conditions' => array( $this->type => 'icon' ),
				'fields'     => array(
					'link' => array(
						'field'       => 'url',
						'type'        => __( 'Icon: Link URL', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'video'       => array(
				'conditions' => array( $this->type => 'video' ),
				'fields'     => array(
					array(
						'field'       => 'link',
						'type'        => __( 'Video: Link', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'vimeo_link',
						'type'        => __( 'Video: Vimeo link', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'login'       => array(
				'conditions' => array( $this->type => 'login' ),
				'fields'     => array(
					array(
						'field'       => 'button_text',
						'type'        => __( 'Login: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'user_label',
						'type'        => __( 'Login: User label', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'user_placeholder',
						'type'        => __( 'Login: User placeholder', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'password_label',
						'type'        => __( 'Login: Password label', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'password_placeholder',
						'type'        => __( 'Login: Password placeholder', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'button'      => array(
				'conditions' => array( $this->type => 'button' ),
				'fields'     => array(
					array(
						'field'       => 'text',
						'type'        => __( 'Button', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					'link' => array(
						'field'       => 'url',
						'type'        => __( 'Button: Link URL', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'html'        => array(
				'conditions' => array( $this->type => 'html' ),
				'fields'     => array(
					array(
						'field'       => 'html',
						'type'        => __( 'HTML', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'image'       => array(
				'conditions' => array( $this->type => 'image' ),
				'fields'     => array(
					array(
						'field'       => 'caption',
						'type'        => __( 'Image: Caption', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					'link' => array(
						'field'       => 'url',
						'type'        => __( 'Image: Link URL', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
			),
			'alert'       => array(
				'conditions' => array( $this->type => 'alert' ),
				'fields'     => array(
					array(
						'field'       => 'alert_title',
						'type'        => __( 'Alert title', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'alert_description',
						'type'        => __( 'Alert description', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'testimonial' => array(
				'conditions' => array( $this->type => 'testimonial' ),
				'fields'     => array(
					array(
						'field'       => 'testimonial_content',
						'type'        => __( 'Testimonial content', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'testimonial_name',
						'type'        => __( 'Testimonial name', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'testimonial_job',
						'type'        => __( 'Testimonial job', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'progress'    => array(
				'conditions' => array( $this->type => 'progress' ),
				'fields'     => array(
					array(
						'field'       => 'title',
						'type'        => __( 'Progress: Title', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'inner_text',
						'type'        => __( 'Progress: Inner text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'counter'     => array(
				'conditions' => array( $this->type => 'counter' ),
				'fields'     => array(
					array(
						'field'       => 'starting_number',
						'type'        => __( 'Starting number', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'title',
						'type'        => __( 'Title', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'icon-box'    => array(
				'conditions' => array( $this->type => 'icon-box' ),
				'fields'     => array(
					array(
						'field'       => 'title_text',
						'type'        => __( 'Icon Box: Title text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'description_text',
						'type'        => __( 'Icon Box: Description text', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'image-box'   => array(
				'conditions' => array( $this->type => 'image-box' ),
				'fields'     => array(
					array(
						'field'       => 'title_text',
						'type'        => __( 'Image Box: Title text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'description_text',
						'type'        => __( 'Image Box: Description text', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
				),
			),
			'flip-box'    => array(
				'conditions' => array( $this->type => 'flip-box' ),
				'fields'     => array(
					array(
						'field'       => 'title_text_a',
						'type'        => __( 'Flip Box: Title text side A', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'description_text_a',
						'type'        => __( 'Flip Box: Description text side A', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'title_text_b',
						'type'        => __( 'Flip Box: Title text side B', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'description_text_b',
						'type'        => __( 'Flip Box: Description text side B', 'wpml-string-translation' ),
						'editor_type' => 'VISUAL'
					),
					array(
						'field'       => 'button_text',
						'type'        => __( 'Flip Box: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
			'toggle'      => array(
				'conditions'        => array( $this->type => 'toggle' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Toggle',
			),
			'accordion'   => array(
				'conditions'        => array( $this->type => 'accordion' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Accordion',
			),
			'tabs'        => array(
				'conditions'        => array( $this->type => 'tabs' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Tabs',
			),
			'price-list'  => array(
				'conditions'        => array( $this->type => 'price-list' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Price_List',
			),
			'icon-list'   => array(
				'conditions'        => array( $this->type => 'icon-list' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Icon_List',
			),
			'slides'      => array(
				'conditions'        => array( $this->type => 'slides' ),
				'fields'            => array(),
				'integration-class' => 'WPML_Elementor_Slides',
			),
			'price-table' => array(
				'conditions'        => array( $this->type => 'price-table' ),
				'fields'            => array(
					array(
						'field'       => 'heading',
						'type'        => __( 'Price Table: Heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'sub_heading',
						'type'        => __( 'Price Table: Sub heading', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'period',
						'type'        => __( 'Price Table: Period', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'button_text',
						'type'        => __( 'Price Table: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'footer_additional_info',
						'type'        => __( 'Price Table: Footer additional info', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'ribbon_title',
						'type'        => __( 'Price Table: Ribbon title', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					'link' => array(
						'field'       => 'url',
						'type'        => __( 'Price Table: Button link', 'wpml-string-translation' ),
						'editor_type' => 'LINK'
					),
				),
				'integration-class' => 'WPML_Elementor_Price_Table',
			),
			'form'        => array(
				'conditions'        => array( $this->type => 'form' ),
				'fields'            => array(
					array(
						'field'       => 'form_name',
						'type'        => __( 'Form: name', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'button_text',
						'type'        => __( 'Form: Button text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'email_subject',
						'type'        => __( 'Form: Email subject', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'email_from_name',
						'type'        => __( 'Form: Email from name', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'success_message',
						'type'        => __( 'Form: Success message', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'error_message',
						'type'        => __( 'Form: Error message', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'required_message',
						'type'        => __( 'Form: Required message', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
					array(
						'field'       => 'invalid_message',
						'type'        => __( 'Form: Invalid message', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
				'integration-class' => 'WPML_Elementor_Form',
			),
			'posts'       => array(
				'conditions' => array( $this->type => 'posts' ),
				'fields'     => array(
					array(
						'field'       => 'classic_read_more_text',
						'type'        => __( 'Posts: Read more text', 'wpml-string-translation' ),
						'editor_type' => 'LINE'
					),
				),
			),
		);

		$this->nodes_to_translate = apply_filters( 'wpml_elementor_widgets_to_translate', $this->nodes_to_translate );
	}
}