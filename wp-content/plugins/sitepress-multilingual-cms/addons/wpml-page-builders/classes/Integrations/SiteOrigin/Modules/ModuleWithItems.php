<?php

namespace WPML\PB\SiteOrigin\Modules;

use WPML\FP\Obj;

abstract class ModuleWithItems implements \IWPML_Page_Builders_Module {

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
	 * @return string
	 */
	abstract public function get_items_field();

	/**
	 * @param mixed $settings
	 *
	 * @return array
	 */
	abstract public function get_items( $settings );

	/**
	 * @param string|int $node_id
	 * @param mixed $settings
	 * @param \WPML_PB_String[] $strings
	 *
	 * @return \WPML_PB_String[]
	 */
	public function get( $node_id, $settings, $strings ) {
		foreach ( $this->get_items( $settings ) as $key => $item ) {
			foreach( $this->get_fields() as $field ) {
				$pathInFlatField = explode( '>', $field );
				$string_value    = Obj::path( $pathInFlatField, $item );
				if ( $string_value ) {
					$strings[] = new \WPML_PB_String(
						$string_value,
						$this->get_string_name( $node_id, $this->get_items_field(), $key, $field ),
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
	 * @param mixed $element
	 * @param \WPML_PB_String $string
	 *
	 * @return array
	 */
	public function update( $node_id, $element, \WPML_PB_String $string ) {
		foreach ( $this->get_items( $element ) as $key => $item ) {
			foreach( $this->get_fields() as $field ) {
				if ( $this->get_string_name( $node_id, $this->get_items_field(), $key, $field ) == $string->get_name() ) {
					$pathInFlatField   = explode( '>', $field );
					$stringInFlatField = Obj::path( $pathInFlatField, $item );
					if ( is_string( $stringInFlatField ) ) {
						$item = Obj::assocPath( $pathInFlatField, $string->get_value(), $item );
					}
					return [ $key, $item ];
				}
			}
		}

		return [];
	}

	private function get_string_name( $node_id, $type, $key, $field ) {
		return $node_id . '-' . $type . '-' . $key . '-' . $field;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function get_field_path( $key ) {
		$path = $this->get_items_field();
		if ( strpos( $path, '>' ) ) {
			list( $parent, $path ) = explode( '>', $path, 2 );
			list( $x, $y )         = explode( '>', $key, 2 );
			return [ $parent, $x, $path, $y ];
		} else {
			return [ $path, $key ];
		}
	}

}
