<?php

class WPML_TM_Rest_Jobs_Element_Info {
	/** @var WPML_TM_Rest_Jobs_Package_Helper_Factory */
	private $package_helper_factory;

	/**
	 * @param WPML_TM_Rest_Jobs_Package_Helper_Factory $package_helper_factory
	 */
	public function __construct( WPML_TM_Rest_Jobs_Package_Helper_Factory $package_helper_factory ) {
		$this->package_helper_factory = $package_helper_factory;
	}


	/**
	 * @param int    $id
	 * @param string $type
	 *
	 * @return array
	 */
	public function get( $id, $type ) {
		$result = array();

		switch ( $type ) {
			case WPML_TM_Job_Entity::POST_TYPE:
				$result = $this->get_for_post( $id );
				break;
			case WPML_TM_Job_Entity::STRING_TYPE:
				$result = $this->get_for_string( $id );
				break;
			case WPML_TM_Job_Entity::PACKAGE_TYPE:
				$result = $this->get_for_package( $id );
				break;
		}

		if ( empty( $result ) ) {
			$result = array(
				'name' => '',
				'url'  => null,
			);
			do_action( 'wpml_tm_jobs_log', 'WPML_TM_Rest_Jobs_Element_Info::get', array( $id, $type ), 'Empty result' );
		}

		$result['url'] = apply_filters( 'wpml_tm_job_list_element_url', $result['url'], $id, $type );

		return $result;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	private function get_for_post( $id ) {
		$result = array();

		$post = get_post( $id );
		if ( $post ) {
			$permalink = get_permalink( $post );

			$result = array(
				'name' => $post->post_title,
				'url'  => $permalink,
			);
		}

		return $result;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	private function get_for_package( $id ) {
		$result = array();

		$helper = $this->package_helper_factory->create();
		if ( ! $helper ) {
			return array(
				'name' => __( 'String package job', 'wpml-translation-management' ),
				'url'  => null,
			);
		}

		$package = $helper->get_translatable_item( null, $id );
		if ( $package ) {
			$result = array(
				'name' => $package->title,
				'url'  => $package->edit_link,
			);
		}

		return $result;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	private function get_for_string( $id ) {
		$result = array();

		if ( ! function_exists( 'icl_get_string_by_id' ) ) {
			return array(
				'name' => __( 'String job', 'wpml-translation-management' ),
				'url'  => null,
			);
		}

		$string = icl_get_string_by_id( $id );
		if ( $string ) {
			$result = array(
				'name' => $string,
				'url'  => null,
			);
		}

		return $result;
	}
}