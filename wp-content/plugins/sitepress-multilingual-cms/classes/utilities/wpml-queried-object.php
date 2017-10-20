<?php

/**
 * Class WPML_Queried_Object
 *
 * @author OnTheGoSystems
 */
class WPML_Queried_Object {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var  null|object */
	private $queried_object;

	/** @var stdClass $queried_object_details */
	private $queried_object_details;

	/**
	 * WPML_TF_Queried_Object constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress      = $sitepress;
		$this->queried_object = get_queried_object();
	}

	/**
	 * @return null|string
	 */
	public function get_source_language_code() {
		return $this->get_queried_object_detail( 'source_language_code' );
	}

	/**
	 * @return string
	 */
	public function get_language_code() {
		return $this->get_queried_object_detail( 'language_code' );
	}

	/**
	 * @param string $key
	 *
	 * @return null|mixed
	 */
	private function get_queried_object_detail( $key ) {
		$detail = null;

		if ( ! $this->queried_object_details ) {

			if ( $this->is_post() ) {

				$this->queried_object_details = $this->sitepress->get_element_language_details(
					$this->get_id(),
					$this->get_element_type()
				);
			}
		}

		if ( isset( $this->queried_object_details->{$key} ) ) {
			$detail = $this->queried_object_details->{$key};
		}

		return $detail;
	}

	/**
	 * @return bool
	 */
	public function is_post() {
		return isset( $this->queried_object->ID, $this->queried_object->post_type );
	}

	/**
	 * @return null|int
	 */
	public function get_id() {
		$id = null;

		if ( isset( $this->queried_object->ID ) ) {
			$id = $this->queried_object->ID;
		}

		return $id;
	}

	/**
	 * @return null|string
	 */
	public function get_element_type() {
		$type = null;

		if ( isset( $this->queried_object->post_type ) ) {
			$type = 'post_' . $this->queried_object->post_type;
		}

		return $type;
	}

	/**
	 * @return null|string
	 */
	public function get_source_url() {
		$url            = null;
		$language_links = $this->sitepress->get_ls_languages();

		if ( isset( $language_links[ $this->get_source_language_code() ]['url'] ) ) {
			$url = $language_links[ $this->get_source_language_code() ]['url'];
		}

		return $url;
	}
}