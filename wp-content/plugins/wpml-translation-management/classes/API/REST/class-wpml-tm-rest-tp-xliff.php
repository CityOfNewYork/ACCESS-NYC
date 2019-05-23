<?php

class WPML_TM_REST_TP_XLIFF extends WPML_REST_Base {
	/** @var WPML_TP_Translations_Repository */
	private $translation_repository;

	/** @var WPML_TM_Rest_Download_File */
	private $download_file;

	public function __construct(
		WPML_TP_Translations_Repository $translation_repository,
		WPML_TM_Rest_Download_File $download_file
	) {
		parent::__construct( 'wpml/tm/v1' );

		$this->translation_repository = $translation_repository;
		$this->download_file          = $download_file;
	}

	public function add_hooks() {
		$this->register_routes();
	}

	public function register_routes() {
		parent::register_route( '/tp/xliff/download/(?P<job_id>\d+)/(?P<job_type>\w+)',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_job_translations_from_tp' ),
				'args'     => array(
					'job_id'   => array(
						'required' => true,
					),
					'job_type' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_job_type' ),
					),
					'json'     => array(
						'type' => 'boolean',
					),
				),
			) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array|string|WP_Error
	 */
	public function get_job_translations_from_tp( WP_REST_Request $request ) {
		try {
			if ( $request->get_param( 'json' ) ) {
				return $this->translation_repository->get_job_translations(
					$request->get_param( 'job_id' ),
					$request->get_param( 'job_type' )
				)->to_array();
			} else {
				return $this->download_job_translation( $request );
			}

		} catch ( Exception $e ) {
			return new WP_Error( 400, $e->getMessage() );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return string
	 */
	private function download_job_translation( WP_REST_Request $request ) {
		try {
			$content = $this->translation_repository->get_job_translations(
				$request->get_param( 'job_id' ),
				$request->get_param( 'job_type' ),
				false
			);
		} catch ( WPML_TP_API_Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}

		$file_name = sprintf( 'job-%d.xliff', $request->get_param( 'job_id' ) );
		return $this->download_file->send( $file_name, $content );
	}

	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return array( WPML_Manage_Translations_Role::CAPABILITY, WPML_Translator_Role::CAPABILITY );
	}

	public function validate_job_type( $value ) {
		return in_array( $value, array(
			WPML_TM_Job_Entity::POST_TYPE,
			WPML_TM_Job_Entity::STRING_TYPE,
			WPML_TM_Job_Entity::PACKAGE_TYPE
		) );
	}
}