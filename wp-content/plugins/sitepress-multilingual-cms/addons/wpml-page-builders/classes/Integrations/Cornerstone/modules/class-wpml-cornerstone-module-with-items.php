<?php

/**
 * Class WPML_Cornerstone_Module_With_Items
 */
abstract class WPML_Cornerstone_Module_With_Items implements IWPML_Page_Builders_Module {

	const ITEMS_FIELD = WPML_Cornerstone_Translatable_Nodes::SETTINGS_FIELD;

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	abstract protected function get_title( $field );

	/** @return array */
	abstract protected function get_fields();

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	abstract protected function get_editor_type( $field );

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	protected function get_items( $settings ) {
		return $settings[ self::ITEMS_FIELD ];
	}

	/**
	 * @param string|int $node_id
	 * @param array $settings
	 * @param WPML_PB_String[] $strings
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $settings, $strings ) {
		foreach ( $this->get_items( $settings ) as $item ) {
			foreach ( $this->get_fields() as $field ) {
				if ( is_array( $item[ $field ] ) ) {
					foreach ( $item[ $field ] as $key => $value ) {
						$strings[] = new WPML_PB_String(
							$value,
							$this->get_string_name( $node_id, $value, $field, $key ),
							$this->get_title( $field ),
							$this->get_editor_type( $field )
						);
					}
				} else {
					$strings[] = new WPML_PB_String(
						$item[ $field ],
						$this->get_string_name( $node_id, $item[ $field ], $field ),
						$this->get_title( $field ),
						$this->get_editor_type( $field )
					);
				}
			}
		}

		return $strings;
	}

	/**
	 * @param string|int $node_id
	 * @param array $settings
	 * @param WPML_PB_String $string
	 *
	 * @return array
	 */
	public function update( $node_id, $settings, WPML_PB_String $string ) {
		foreach ( $this->get_items( $settings ) as $key => $item ) {
			foreach ( $this->get_fields() as $field ) {
				if ( $this->get_string_name( $node_id, $item[ $field ], $field ) === $string->get_name() ) {
					$settings[ self::ITEMS_FIELD ][ $key ][ $field ] = $string->get_value();
				}
			}
		}

		return $settings;
	}

	private function get_string_name( $node_id, $value, $type, $key = '' ) {
		return md5( $value ) . '-' . $type . $key . '-' . $node_id;
	}

}