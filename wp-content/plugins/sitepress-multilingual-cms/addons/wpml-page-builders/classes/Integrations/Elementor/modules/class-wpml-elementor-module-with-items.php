<?php

use WPML\FP\Obj;

/**
 * Class WPML_Elementor_Module_With_Items
 */
abstract class WPML_Elementor_Module_With_Items implements IWPML_Page_Builders_Module {

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
	 * @return string mixed
	 */
	abstract protected function get_editor_type( $field );

	/**
	 * @return string
	 */
	abstract public function get_items_field();

	/**
	 * @param string|int $node_id
	 * @param array $element
	 * @param WPML_PB_String[] $strings
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $element, $strings ) {
		foreach ( $this->get_items( $element ) as $item ) {
			foreach( $this->get_fields() as $key => $field ) {
				if ( ! is_array( $field ) ) {

					if ( ! isset( $item[ $field ] ) ) {
						continue;
					}

					$strings[] = new WPML_PB_String(
						$item[ $field ],
						$this->get_string_name( $node_id, $item, $element, $field ),
						$this->get_title( $field ),
						$this->get_editor_type( $field )
					);
				} else {
					foreach ( $field as $inner_field ) {

						if ( ! isset( $item[ $key ][ $inner_field ] ) ) {
							continue;
						}

						$strings[] = new WPML_PB_String(
							$item[ $key ][ $inner_field ],
							$this->get_string_name( $node_id, $item, $element, $inner_field, $key ),
							$this->get_title( $inner_field ),
							$this->get_editor_type( $inner_field )
						);
					}
				}
			}
		}
		return $strings;
	}

	/**
	 * @param int|string $node_id
	 * @param mixed $element
	 * @param WPML_PB_String $string
	 *
	 * @return mixed
	 */
	public function update( $node_id, $element, WPML_PB_String $string ) {
		foreach ( $this->get_items( $element ) as $key => $item ) {
			foreach( $this->get_fields() as $field_key => $field ) {
				if ( ! is_array( $field ) ) {

					if ( ! isset( $item[ $field ] ) ) {
						continue;
					}

					if ( $this->get_string_name( $node_id, $item, $element, $field ) === $string->get_name() ) {
						$item[ $field ] = $string->get_value();
						$item['index'] = $key;
						return $item;
					}
				} else {
					foreach ( $field as $inner_field ) {
						if ( ! isset( $item[ $field_key ][ $inner_field ] ) ) {
							continue;
						}

						if ( $this->get_string_name( $node_id, $item, $element, $inner_field, $field_key ) === $string->get_name() ) {
							$item[ $field_key ][ $inner_field ] = $string->get_value();
							$item['index'] = $key;
							return $item;
						}
					}
				}
			}
		}
	}

	/**
	 * @param string $nodeId
	 * @param array  $item
	 * @param array  $element
	 * @param string $field
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_string_name( $nodeId, $item, $element, $field = '', $key = '' ) {
		$widgetType   = Obj::prop( 'widgetType', $element );
		$itemId       = Obj::prop( '_id', $item );
		$name         =  $widgetType . '-' . $field . '-' . $nodeId . '-' . $itemId;

		/**
		 * Filter a package string name.
		 *
		 * Could be used for repeater or nested fields with the same key.
		 *
		 * @since 2.0.5
		 *
		 * @param string $name
		 * @param array  $args {
		 *     @type string $nodeId  Elementor node id.
		 *     @type array  $item    The item that is being registered.
		 *     @type array  $element The element that is being processed and registered.
		 *     @type string $field   Optional. The item field that is being registered.
		 *     @type string $key     Optional. The item field sub-key that is being registered.
		 * }
		 */
		return apply_filters( "wpml_pb_elementor_register_string_name_$widgetType", $name, [
			'nodeId'  => $nodeId,
			'item'    => $item,
			'element' => $element,
			'field'   => $field,
			'key'     => $key,
		] );
	}

	/**
	 * @param array $element
	 *
	 * @return mixed
	 */
	public function get_items( $element ) {
		return $element[ WPML_Elementor_Translatable_Nodes::SETTINGS_FIELD ][ $this->get_items_field() ];
	}

}
