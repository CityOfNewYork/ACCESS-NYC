<?php

/**
 * @author OnTheGo Systems
 *
 * AMS: https://git.onthegosystems.com/ate/ams/wikis/home
 * ATE: https://git.onthegosystems.com/ate/ams/wikis/home (https://bitbucket.org/emartini_crossover/ate/wiki/browse/API/V1/jobs)
 */
class WPML_TM_ATE_AMS_Endpoints {
	const AMS_BASE_URL               = 'https://ams.wpml.org';
	const ATE_BASE_URL               = 'https://ate.wpml.org';
	const ATE_JOB_STATUS_CREATED     = 0;
	const ATE_JOB_STATUS_TRANSLATING = 1;
	const ATE_JOB_STATUS_TRANSLATED  = 6;
	const ATE_JOB_STATUS_DELIVERING  = 7;
	const ATE_JOB_STATUS_DELIVERED   = 8;
	const ATE_JOB_STATUS_EDITED      = 15;

	/**
	 * AMS
	 */
	const ENDPOINTS_AUTO_LOGIN          = '/panel/autologin';
	const ENDPOINTS_CLIENTS             = '/api/wpml/clients';
	const ENDPOINTS_CONFIRM             = '/api/wpml/jobs/confirm';
	const ENDPOINTS_EDITOR              = '/api/wpml/jobs/{job_id}/open?translator={translator_email}&return_url={return_url}';
	const ENDPOINTS_SUBSCRIPTION        = '/api/wpml/websites/translators/{translator_email}/enable';
	const ENDPOINTS_SUBSCRIPTION_STATUS = '/api/wpml/websites/{WEBSITE_UUID}/translators/{translator_email}';
	const ENDPOINTS_WEBSITES            = '/api/wpml/websites';
	const ENDPOINTS_CREDITS             = '/api/wpml/credits';
	const ENDPOINTS_RESUME_ALL          = '/api/wpml/jobs/resume/all';
	const ENDPOINTS_SEND_SITEKEY        = '/api/wpml/websites/assign_key';
	const ENDPOINTS_TRANSLATION_ENGINES = '/api/wpml/engines';

	/**
	 * AMS CLONED SITES
	 */
	const ENDPOINTS_SITE_COPY       = '/api/wpml/websites/copy';
	const ENDPOINTS_SITE_MOVE       = '/api/wpml/websites/move';
	const ENDPOINTS_SITE_CONFIRM    = '/api/wpml/websites/confirm';
	const ENDPOINTS_COPY_ATTACHED   = '/api/wpml/websites/copy_attached';

	/**
	 * ATE
	 */
	const ENDPOINTS_JOB                 = '/api/wpml/job';
	const ENDPOINTS_JOBS                = '/api/wpml/jobs';
	const ENDPOINT_JOBS_BY_WPML_JOB_IDS = '/api/wpml/jobs/wpml';
	const ENDPOINT_JOBS_STATUSES        = '/api/wpml/jobs/statuses';
	const ENDPOINTS_MANAGERS            = '/api/wpml/websites/translation_managers';
	const ENDPOINTS_SITE                = '/api/wpml/websites/create_unique';
	const ENDPOINTS_STATUS              = '/api/wpml/access_keys/{SHARED_KEY}/status';
	const ENDPOINTS_TRANSLATORS         = '/api/wpml/websites/translators';
	const ENDPOINT_SOURCE_ID_MIGRATION  = '/api/wpml/migration';
	const ENDPOINTS_SYNC_ALL            = '/api/wpml/sync/all';
	const ENDPOINTS_SYNC_PAGE           = '/api/wpml/sync/page';
	const ENDPOINTS_RETRANSLATE         = '/api/wpml/retranslations/sync';
	const ENDPOINTS_CLONE_JOB           = '/api/wpml/jobs/%s/clone';
	const ENDPOINTS_CANCEL_JOBS         = '/api/wpml/jobs/cancel';
	const ENDPOINTS_HIDE_JOBS           = '/api/wpml/jobs/canceled_on_wpml';
	const ENDPOINTS_LANGUAGES           = '/api/wpml/languages';
	const ENDPOINTS_LANGUAGES_MAPPING   = '/api/wpml/languages/mappings';
	const ENDPOINTS_LANGUAGES_MAPPING_DELETE = '/api/wpml/languages/delete_mapping';
	const ENDPOINTS_LANGUAGES_CHECK_PAIRS  = '/api/wpml/languages/check_pairs';
	const ENDPOINTS_LANGUAGES_SHOW      = '/api/wpml/languages/%s';
	const SERVICE_AMS                   = 'ams';
	const SERVICE_ATE                   = 'ate';

	const STORE_JOB      = '/ate/jobs/store';
	const SYNC_JOBS      = '/ate/jobs/sync';
	const DOWNLOAD_JOBS  = '/ate/jobs/download';
	const RETRY_JOBS = '/ate/jobs/retry';
	const FIX_JOB      = '/ate/jobs/(?P<ateJobId>\d+)/fix';

	/**
	 * ICL to ATE migration
	 */
	const ENDPOINTS_IMPORT_TRANSLATORS_FROM_ICL = '/api/wpml/icl/translators/import';
	const ENDPOINTS_START_MIGRATION_IMPORT_FROM_ICL = '/api/wpml/icl/translations/import/start';
	const ENDPOINTS_CHECK_STATUS_MIGRATION_IMPORT_FROM_ICL = '/api/wpml/icl/translations/import/status';


	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_auto_login() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_AUTO_LOGIN );
	}

	/**
	 * @param string     $service
	 * @param string     $endpoint
	 * @param array|null $query_string
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_endpoint_url( $service, $endpoint, array $query_string = null ) {
		$url = $this->get_base_url( $service ) . $endpoint;

		if ( $query_string ) {
			$url_parts = wp_parse_url( $url );
			$query     = array();
			if ( $url_parts && array_key_exists( 'query', $url_parts ) ) {
				parse_str( $url_parts['query'], $query );
			}

			foreach ( $query_string as $key => $value ) {
				if ( $value ) {
					if ( is_scalar( $value ) ) {
						$query[ $key ] = $value;
					} else {
						$query[ $key ] = implode( ',', $value );
					}
				}
			}
			$url_parts['query'] = http_build_query( $query );

			$url = http_build_url( $url_parts );
		}

		return $url;
	}

	/**
	 * @param $service
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_base_url( $service ) {
		switch ( $service ) {
			case self::SERVICE_AMS:
				return $this->get_AMS_base_url();
			case self::SERVICE_ATE:
				return $this->get_ATE_base_url();
			default:
				throw new InvalidArgumentException( $service . ' is not a valid argument' );
		}
	}

	private function get_AMS_base_url() {
		return $this->get_service_base_url( self::SERVICE_AMS );
	}

	private function get_ATE_base_url() {
		return $this->get_service_base_url( self::SERVICE_ATE );
	}

	private function get_service_base_url( $service ) {
		$constant_name = strtoupper( $service ) . '_BASE_URL';

		$url = constant( __CLASS__ . '::' . $constant_name );

		if ( defined( $constant_name ) ) {
			$url = constant( $constant_name );
		}
		if ( getenv( $constant_name ) ) {
			$url = getenv( $constant_name );
		}

		return $url;
	}

	public function get_AMS_host() {
		return $this->get_service_host( self::SERVICE_AMS );
	}

	public function get_ATE_host() {
		return $this->get_service_host( self::SERVICE_ATE );
	}

	private function get_service_host( $service ) {
		$base_url = $this->get_service_base_url( $service );

		$url_parts = wp_parse_url( $base_url );

		return $url_parts['host'];
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_register_client() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SITE );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_status() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_STATUS );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_synchronize_managers() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_MANAGERS );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_synchronize_translators() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_TRANSLATORS );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_site_copy() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SITE_COPY );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_copy_attached() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_COPY_ATTACHED );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_site_move() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SITE_MOVE );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ams_site_confirm() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SITE_CONFIRM );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_enable_subscription() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SUBSCRIPTION );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_subscription_status() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SUBSCRIPTION_STATUS );
	}

	/**
	 * @param int|string|array $job_params
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ate_confirm_job( $job_params = null ) {
		$job_id_part = $this->parse_job_params( $job_params );

		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_CONFIRM . $job_id_part );
	}

	public function get_translation_engines() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_TRANSLATION_ENGINES );
	}

	/**
	 * @param null|int|string|array $job_params
	 *
	 * @return string
	 */
	private function parse_job_params( $job_params ) {
		$job_id_part = '';

		if ( $job_params ) {
			if ( is_array( $job_params ) ) {
				$job_ids = implode( ',', $job_params );
			} else {
				$job_ids = $job_params;
			}
			$job_id_part = '/' . $job_ids;
		}

		return $job_id_part;
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ate_editor() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_EDITOR );
	}

	/**
	 * @param null|int|string|array $job_params
	 * @param null|array            $statuses
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ate_jobs( $job_params = null, array $statuses = null ) {
		$job_id_part = $this->parse_job_params( $job_params );

		return $this->get_endpoint_url(
			self::SERVICE_ATE,
			self::ENDPOINTS_JOBS . $job_id_part,
			array( 'status' => $statuses ) );
	}

	public function getAteCancelJobs() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_CANCEL_JOBS );
	}

	public function getAteHideJobs() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_HIDE_JOBS );
	}

	public function getLanguages() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_LANGUAGES );
	}

	public function getLanguagesMapping() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_LANGUAGES_MAPPING );
	}

	public function getDeleteLanguagesMapping() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_LANGUAGES_MAPPING_DELETE );
	}

	public function getLanguagesCheckPairs() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_LANGUAGES_CHECK_PAIRS );
	}

	public function getShowLanguage() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_LANGUAGES_SHOW );
	}

	public function startTranlsationMemoryIclMigration(){
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_START_MIGRATION_IMPORT_FROM_ICL );
	}

	public function checkStatusTranlsationMemoryIclMigration(){
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_CHECK_STATUS_MIGRATION_IMPORT_FROM_ICL );
	}

	public function importIclTranslators(){
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_IMPORT_TRANSLATORS_FROM_ICL);
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_ate_job_status(  ) {
		return $this->get_endpoint_url(
			self::SERVICE_ATE,
			self::ENDPOINT_JOBS_STATUSES
		);
	}

	/**
	 * @param int() $job_ids
	 *
	 * @return string
	 */
	public function get_ate_jobs_by_wpml_job_ids( $job_ids ) {
		return $this->get_endpoint_url( self::SERVICE_ATE,
			self::ENDPOINT_JOBS_BY_WPML_JOB_IDS,
			array(
				'site_identifier' => wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE ),
				'wpml_job_ids'    => $job_ids,
			) );
	}

	/**
	 * @return string
	 */
	public function get_websites() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_WEBSITES );
	}

	/**
	 * @return string
	 */
	public function get_source_id_migration() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINT_SOURCE_ID_MIGRATION );
	}

	/**
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function get_retranslate(): string {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_RETRANSLATE );
	}

	/**
	 * @return string
	 */
	public function get_sync_all() {
		return $this->get_endpoint_url( self::SERVICE_ATE, self::ENDPOINTS_SYNC_ALL );
	}

	/**
	 * @param string $paginationToken
	 * @param int    $page
	 *
	 * @return string
	 */
	public function get_sync_page( $paginationToken, $page ) {
		return $this->get_endpoint_url(
			self::SERVICE_ATE,
			self::ENDPOINTS_SYNC_PAGE,
			[
				'pagination_token' => $paginationToken,
				'page'             => $page,
			]
		);
	}

	/**
	 * @param int $job_id
	 *
	 * @return string
	 */
	public function get_clone_job( $job_id ) {
		return $this->get_endpoint_url(
			self::SERVICE_ATE,
			sprintf( self::ENDPOINTS_CLONE_JOB, $job_id )
		);
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_credits() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_CREDITS );
	}

	/**
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_resume_all() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_RESUME_ALL );
	}

	public function get_send_sitekey() {
		return $this->get_endpoint_url( self::SERVICE_AMS, self::ENDPOINTS_SEND_SITEKEY );
	}
}
