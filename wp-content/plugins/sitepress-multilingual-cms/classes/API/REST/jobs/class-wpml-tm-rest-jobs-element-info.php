<?php

use WPML\FP\Obj;

class WPML_TM_Rest_Jobs_Element_Info {
	/** @var WPML_TM_Rest_Jobs_Package_Helper_Factory */
	private $package_helper_factory;

	/** @var array|null */
	private $post_types;

	/**
	 * @param WPML_TM_Rest_Jobs_Package_Helper_Factory $package_helper_factory
	 */
	public function __construct( WPML_TM_Rest_Jobs_Package_Helper_Factory $package_helper_factory ) {
		$this->package_helper_factory = $package_helper_factory;
	}


	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return array
	 */
	public function get( WPML_TM_Job_Entity $job ) {
		$type   = $job->get_type();
		$id     = $job->get_original_element_id();
		$result = [];

		switch ( $type ) {
			case WPML_TM_Job_Entity::POST_TYPE:
				/** @var WPML_TM_Post_Job_Entity $job */
				$result = $this->get_for_post( $id, $job->get_element_id() );
				break;
			case WPML_TM_Job_Entity::STRING_TYPE:
			case WPML_TM_Job_Entity::STRING_BATCH:
				$result = $this->get_for_title( $job->get_title() );
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

		if ( $job instanceof WPML_TM_Post_Job_Entity ) {
			$result['type'] = $this->get_type_info( $job );
		}

		return $result;
	}

	/**
	 * @param int $originalPostId
	 * @param int $translatedPostId
	 *
	 * @return array
	 */
	private function get_for_post( $originalPostId, $translatedPostId ) {
		$result = array();

		$post = get_post( $originalPostId );
		if ( $post ) {
			$permalink = get_permalink( $post );

			$result = [
				'name'   => $post->post_title,
				'url'    => $permalink,
				'status' => Obj::propOr( 'draft', 'post_status', get_post( $translatedPostId ) ),
			];
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
	 * @param string $title
	 *
	 * @return array
	 */
	private function get_for_title( $title ) {
		return [
			'name' => $title,
			'url'  => null,
		];
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 *
	 * @return array
	 */
	private function get_type_info( WPML_TM_Post_Job_Entity $job ) {
		$generalType = substr(
			$job->get_element_type(),
			0,
			strpos( $job->get_element_type(), '_' ) ?: 0
		);

		switch ( $generalType ) {
			case 'post':
			case 'package':
				$specificType = substr( $job->get_element_type(), strlen( $generalType ) + 1 );
				$label        = Obj::pathOr(
					$job->get_element_type(),
					[ $specificType, 'labels', 'singular_name' ],
					$this->get_post_types()
				);
				break;
			case 'st-batch':
				$label = __( 'Strings', 'wpml-translation-management' );
				break;
			default:
				$label = $job->get_element_type();
		}

		return [
			'value' => $job->get_element_type(),
			'label' => $label,
		];
	}

	private function get_post_types() {
		if ( $this->post_types === null ) {
			$this->post_types = \WPML\API\PostTypes::getTranslatableWithInfo();
		}

		return $this->post_types;
	}
}
