<?php

namespace WPML\PB\SiteOrigin;

use WPML\FP\Obj;
use WPML\PB\SiteOrigin\Modules\ModuleWithItemsFromConfig;

class TranslatableNodes implements \IWPML_Page_Builders_Translatable_Nodes {

	const SETTINGS_FIELD = 'panels_info';
	const CHILDREN_FIELD = 'panels_data';

	const WRAPPING_MODULES = [
		'SiteOrigin_Panels_Widgets_Layout',
	];

	/**
	 * Nodes to translate.
	 *
	 * @var array
	 */
	private $translatableNodes;

	/**
	 * Get translatable node.
	 *
	 * @param string|int $node_id  Node id.
	 * @param array      $settings Node settings.
	 *
	 * @return \WPML_PB_String[]
	 */
	public function get( $node_id, $settings ) {
		$strings = [];

		foreach ( $this->getTranslatableNodes() as $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_key       = $field['field'];
					$pathInFlatField = self::get_partial_path( $field_key );
					$string_value    = Obj::path( $pathInFlatField, $settings );

					if ( $string_value ) {

						$string = new \WPML_PB_String(
							$string_value,
							$this->get_string_name( $node_id, $field, $settings ),
							$field['type'],
							$field['editor_type'],
							$this->get_wrap_tag( $settings )
						);

						$strings[] = $string;
					}
				}

				foreach ( $this->get_integration_instances( $node_data ) as $node ) {
					$strings = $node->get( $node_id, $settings, $strings );
				}
			}
		}

		return $strings;
	}

	/**
	 * Update translatable node.
	 *
	 * @param string          $node_id  Node id.
	 * @param array           $settings Node settings.
	 * @param \WPML_PB_String $string   String object.
	 *
	 * @return mixed
	 */
	public function update( $node_id, $settings, \WPML_PB_String $string ) {
		foreach ( $this->getTranslatableNodes() as $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_key = $field['field'];
					if ( $this->get_string_name( $node_id, $field, $settings ) === $string->get_name() ) {
						$pathInFlatField   = self::get_partial_path( $field_key );
						$stringInFlatField = Obj::path( $pathInFlatField, $settings );

						if ( is_string( $stringInFlatField ) ) {
							$settings = Obj::assocPath( $pathInFlatField, $string->get_value(), $settings );
						}
					}
				}

				foreach ( $this->get_integration_instances( $node_data ) as $node ) {
					list( $key, $item ) = $node->update( $node_id, $settings, $string );
					if ( $item ) {
						if ( strpos( $key, '>' ) ) {
							$pathInFlatField = $node->get_field_path( $key );
						} else {
							$pathInFlatField   = self::get_partial_path( $node->get_items_field() );
							$pathInFlatField[] = $key;
						}
						$settings = Obj::assocPath( $pathInFlatField, $item, $settings );
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * @param string $field
	 *
	 * @return string[]
	 */
	private static function get_partial_path( $field ) {
		return explode( '>', $field );
	}

	/**
	 * @param array $node_data
	 *
	 * @return ModuleWithItemsFromConfig[]
	 */
	private function get_integration_instances( array $node_data ) {
		$instances = [];

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
	 * @param string $node_id  Node id.
	 * @param array  $field    Page builder field.
	 * @param array  $settings Node settings.
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $node_id . '-' . $settings[ self::SETTINGS_FIELD ]['id'] . '-' . $field['field'];
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
		foreach ( $node_data['conditions'] as $field_value ) {
			if ( $settings[ self::SETTINGS_FIELD ]['class'] !== $field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	private function getTranslatableNodes() {
		if ( null === $this->translatableNodes ) {
			$this->translatableNodes = $this->initialize_nodes_to_translate();
		}

		return $this->translatableNodes;
	}

	public function initialize_nodes_to_translate() {
		return apply_filters( 'wpml_siteorigin_modules_to_translate', [] );
	}

	/**
	 * @param array $module
	 *
	 * @return bool
	 */
	public static function isWrappingModule( $module ) {
		return isset( $module[ self::CHILDREN_FIELD ] ) &&
			in_array( Obj::path( [ self::SETTINGS_FIELD, 'class' ], $module ), self::WRAPPING_MODULES, true );
	}
}
