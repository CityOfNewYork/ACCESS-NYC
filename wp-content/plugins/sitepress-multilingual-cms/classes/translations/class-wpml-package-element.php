<?php

use WPML\FP\Obj;

class WPML_Package_Element extends WPML_Translation_Element {

	/** @var string */
	protected $kind_slug;

	/**
	 * @param int           $id
	 * @param SitePress     $sitepress
	 * @param string        $kind_slug
	 * @param WPML_WP_Cache $wpml_cache
	 */
	public function __construct( $id, SitePress $sitepress, $kind_slug = '', WPML_WP_Cache $wpml_cache = null ) {
		$this->kind_slug = $kind_slug;
		parent::__construct( $id, $sitepress, $wpml_cache );
	}

	/**
	 * @return null
	 */
	public function get_wp_object() {
		return null;
	}

	/**
	 * @param WPML_Package $element
	 *
	 * @return string
	 */
	public function get_type( $element = null ) {
		if ( ! $this->kind_slug && $element instanceof WPML_Package ) {
			$this->kind_slug = $element->kind_slug;
		}

		return $this->kind_slug;
	}

	/**
	 * @return string
	 */
	public function get_wpml_element_type() {
		return $this->get_element_type() . '_' . $this->get_type();
	}

	/**
	 * @return string
	 */
	public function get_element_type() {
		return 'package';
	}

	/**
	 * @return int
	 */
	public function get_element_id() {
		return $this->id;
	}

	/**
	 * @param null|WPML_Package $element_data
	 *
	 * @return WPML_Package_Element
	 * @throws \InvalidArgumentException Exception.
	 */
	public function get_new_instance( $element_data ) {
		$id        = Obj::prop( 'ID', $element_data );
		$kind_slug = Obj::prop( 'kind_slug', $element_data );

		return new WPML_Package_Element( $id, $this->sitepress, $kind_slug, $this->wpml_cache );
	}

	/**
	 * @return true
	 */
	public function is_translatable() {
		return true;
	}

	/**
	 * @return true
	 */
	public function is_display_as_translated() {
		return true;
	}
}
