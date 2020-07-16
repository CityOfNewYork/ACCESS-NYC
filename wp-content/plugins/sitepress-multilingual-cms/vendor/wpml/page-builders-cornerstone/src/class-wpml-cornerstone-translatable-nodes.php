<?php
/**
 * WPML_Cornerstone_Translatable_Nodes class file.
 *
 * @package wpml-page-builders-cornerstone
 */

/**
 * Class WPML_Cornerstone_Translatable_Nodes
 */
class WPML_Cornerstone_Translatable_Nodes implements IWPML_Page_Builders_Translatable_Nodes {

	const SETTINGS_FIELD = '_modules';

	/**
	 * Nodes to translate.
	 *
	 * @var array
	 */
	protected $nodes_to_translate;

	/**
	 * Get translatable node.
	 *
	 * @param string|int $node_id  Node id.
	 * @param array      $settings Node settings.
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
					if ( isset( $settings[ $field_key ] ) && trim( $settings[ $field_key ] ) ) {

						$string = new WPML_PB_String(
							$settings[ $field_key ],
							$this->get_string_name( $node_id, $field, $settings ),
							$field['type'],
							$field['editor_type'],
							$this->get_wrap_tag( $settings )
						);

						$strings[] = $string;
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						/**
						 * Node object.
						 *
						 * @var WPML_Cornerstone_Module_With_Items $node
						 */
						$node    = new $node_data['integration-class']();
						$strings = $node->get( $node_id, $settings, $strings );
						// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					} catch ( Exception $e ) {
						// Nothing to do with the exception, we do not handle it.
					}
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
	 * @param array          $settings Node settings.
	 * @param WPML_PB_String $string   String object.
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
					if ( $this->get_string_name( $node_id, $field, $settings ) === $string->get_name() ) {
						$settings[ $field_key ] = $string->get_value();
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						/**
						 * Node object.
						 *
						 * @var WPML_Cornerstone_Module_With_Items $node
						 */
						$node     = new $node_data['integration-class']();
						$settings = $node->update( $node_id, $settings, $string );
						// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					} catch ( Exception $e ) {
						// Nothing to do with the exception, we do not handle it.
					}
					// phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}
		}

		return $settings;
	}

	/**
	 * Get string name.
	 *
	 * @param string $node_id  Node id.
	 * @param array  $field    Page builder field.
	 * @param array  $settings Node settings.
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $field['field'] . '-' . $settings['_type'] . '-' . $node_id;
	}

	/**
	 * Get wrap tag for string.
	 * Used for SEO, can contain (h1...h6, etc.)
	 *
	 * @param array $settings Field settings.
	 *
	 * @return string
	 */
	private function get_wrap_tag( $settings ) {
		if ( isset( $settings['_type'] ) && 'headline' === $settings['_type'] && isset( $settings['text_tag'] ) ) {
			return $settings['text_tag'];
		}

		return '';
	}

	/**
	 * Check if node condition is ok.
	 *
	 * @param array $node_data Node data.
	 * @param array $settings  Node settings.
	 *
	 * @return bool
	 */
	private function conditions_ok( $node_data, $settings ) {
		$conditions_meet = true;
		foreach ( $node_data['conditions'] as $field_key => $field_value ) {
			if ( ! isset( $settings[ $field_key ] ) || $settings[ $field_key ] !== $field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	/**
	 * @return array[]
	 */
	public static function get_nodes_to_translate() {
		return [
			'card'                  => [
				'conditions' => [ '_type' => 'card' ],
				'fields'     => [
					[
						'field'       => 'card_front_text_content',
						'type'        => __( 'Card: front text content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
					[
						'field'       => 'card_back_text_content',
						'type'        => __( 'Card: back text content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
					[
						'field'       => 'anchor_text_primary_content',
						'type'        => __( 'Card: anchor text primary content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
					[
						'field'       => 'anchor_text_secondary_content',
						'type'        => __( 'Card: anchor text secondary content', 'sitepress' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'anchor_href',
						'type'        => __( 'Card: anchor link', 'sitepress' ),
						'editor_type' => 'LINK',
					],
				],
			],
			'alert'                   => [
				'conditions' => [ '_type' => 'alert' ],
				'fields'     => [
					[
						'field'       => 'alert_content',
						'type'        => __( 'Alert Content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'text'                    => [
				'conditions' => [ '_type' => 'text' ],
				'fields'     => [
					[
						'field'       => 'text_content',
						'type'        => __( 'Text content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'quote'                   => [
				'conditions' => [ '_type' => 'quote' ],
				'fields'     => [
					[
						'field'       => 'quote_content',
						'type'        => __( 'Quote content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
					[
						'field'       => 'quote_cite_content',
						'type'        => __( 'Quote: cite content', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'counter'                 => [
				'conditions' => [ '_type' => 'counter' ],
				'fields'     => [
					[
						'field'       => 'counter_number_prefix_content',
						'type'        => __( 'Counter: number prefix', 'sitepress' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'counter_number_suffix_content',
						'type'        => __( 'Counter: number suffix', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'content-area'            => [
				'conditions' => [ '_type' => 'content-area' ],
				'fields'     => [
					[
						'field'       => 'content',
						'type'        => __( 'Content Area: content', 'sitepress' ),
						'editor_type' => 'AREA',
					],
				],
			],
			'breadcrumbs'             => [
				'conditions' => [ '_type' => 'breadcrumbs' ],
				'fields'     => [
					[
						'field'       => 'breadcrumbs_home_label_text',
						'type'        => __( 'Breadcrumbs: home label text', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'audio'                   => [
				'conditions' => [ '_type' => 'audio' ],
				'fields'     => [
					[
						'field'       => 'audio_embed_code',
						'type'        => __( 'Audio: embed code', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'headline'                => [
				'conditions' => [ '_type' => 'headline' ],
				'fields'     => [
					[
						'field'       => 'text_content',
						'type'        => __( 'Headline text content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'content-area-off-canvas' => [
				'conditions' => [ '_type' => 'content-area-off-canvas' ],
				'fields'     => [
					[
						'field'       => 'off_canvas_content',
						'type'        => __( 'Canvas content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'content-area-modal'      => [
				'conditions' => [ '_type' => 'content-area-modal' ],
				'fields'     => [
					[
						'field'       => 'modal_content',
						'type'        => __( 'Modal content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'content-area-dropdown'   => [
				'conditions' => [ '_type' => 'content-area-dropdown' ],
				'fields'     => [
					[
						'field'       => 'dropdown_content',
						'type'        => __( 'Dropdown content', 'sitepress' ),
						'editor_type' => 'VISUAL',
					],
				],
			],
			'button'                  => [
				'conditions' => [ '_type' => 'button' ],
				'fields'     => [
					[
						'field'       => 'anchor_text_primary_content',
						'type'        => __( 'Anchor text: primary content', 'sitepress' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'anchor_text_secondary_content',
						'type'        => __( 'Anchor text: secondary content', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'video'                   => [
				'conditions' => [ '_type' => 'video' ],
				'fields'     => [
					[
						'field'       => 'video_embed_code',
						'type'        => __( 'Video: embed code', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'search-inline'           => [
				'conditions' => [ '_type' => 'search-inline' ],
				'fields'     => [
					[
						'field'       => 'search_placeholder',
						'type'        => __( 'Search Inline: placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'search-modal'            => [
				'conditions' => [ '_type' => 'search-modal' ],
				'fields'     => [
					[
						'field'       => 'search_placeholder',
						'type'        => __( 'Search Modal: placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'search-dropdown'         => [
				'conditions' => [ '_type' => 'search-dropdown' ],
				'fields'     => [
					[
						'field'       => 'search_placeholder',
						'type'        => __( 'Search Dropdown: placeholder', 'sitepress' ),
						'editor_type' => 'LINE',
					],
				],
			],
			'accordion'               => [
				'conditions'        => [ '_type' => 'accordion' ],
				'fields'            => [],
				'integration-class' => 'WPML_Cornerstone_Accordion',
			],
			'tabs'                    => [
				'conditions'        => [ '_type' => 'tabs' ],
				'fields'            => [],
				'integration-class' => 'WPML_Cornerstone_Tabs',
			],
		];
	}

	/**
	 * Initialize translatable nodes.
	 */
	public function initialize_nodes_to_translate() {
		$this->nodes_to_translate = apply_filters( 'wpml_cornerstone_modules_to_translate', self::get_nodes_to_translate() );
	}
}
