<?php

interface IWPML_Page_Builders_Translatable_Nodes {

	/**
	 * @param string|int $node_id
	 * @param array|stdClass $element
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $element );

	/**
	 * @param string|int $node_id
	 * @param array|stdClass $element
	 * @param WPML_PB_String $string
	 *
	 * @return mixed
	 */
	public function update( $node_id, $element, WPML_PB_String $string );

	/**
	 * @param string $node_id
	 * @param array $field
	 * @param mixed $settings
	 *
	 * @return mixed
	 */
	public function get_string_name( $node_id, $field, $settings );

	public function initialize_nodes_to_translate();
}