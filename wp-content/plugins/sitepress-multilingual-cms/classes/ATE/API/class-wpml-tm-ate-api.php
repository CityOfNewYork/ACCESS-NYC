<?php

use WPML\TM\ATE\API\FingerprintGenerator;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\EventsTypes;
use WPML\TM\ATE\ClonedSites\ApiCommunication as ClonedSitesHandler;
use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\FP\Logic;
use WPML\FP\Str;
use WPML\Element\API\Entity\LanguageMapping;
use WPML\LIB\WP\WordPress;
use WPML\TM\Editor\ATEDetailedErrorMessage;
use function WPML\FP\invoke;
use function WPML\FP\pipe;
use WPML\Element\API\Languages;
use WPML\FP\Relation;
use WPML\FP\Maybe;
use WPML\TM\ATE\API\RequestException;

/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE_API {

	const TRANSLATED = 6;
	const DELIVERING = 7;
	const NOT_ENOUGH_CREDIT_STATUS = 31;
	const CANCELLED_STATUS = 20;
	const SHOULD_HIDE_STATUS = 42;

	private $wp_http;
	private $auth;
	private $endpoints;

	/**
	 * @var ClonedSitesHandler
	 */
	private $clonedSitesHandler;

	/**
	 * @var FingerprintGenerator
	 */
	private $fingerprintGenerator;

	/**
	 * WPML_TM_ATE_API constructor.
	 *
	 * @param WP_Http                    $wp_http
	 * @param WPML_TM_ATE_Authentication $auth
	 * @param WPML_TM_ATE_AMS_Endpoints  $endpoints
	 * @param ClonedSitesHandler  $clonedSitesHandler
	 * @param FingerprintGenerator  $fingerprintGenerator
	 */
	public function __construct(
		WP_Http $wp_http,
		WPML_TM_ATE_Authentication $auth,
		WPML_TM_ATE_AMS_Endpoints $endpoints,
		ClonedSitesHandler $clonedSitesHandler,
		FingerprintGenerator $fingerprintGenerator
	) {
		$this->wp_http              = $wp_http;
		$this->auth                 = $auth;
		$this->endpoints            = $endpoints;
		$this->clonedSitesHandler   = $clonedSitesHandler;
		$this->fingerprintGenerator = $fingerprintGenerator;
	}

	/**
	 * On success, it returns the map: wpmlJobId => ateJobId inside the 'jobs' key.
	 *
	 * @param array $params
	 * @see https://bitbucket.org/emartini_crossover/ate/wiki/API/V1/jobs/create
	 *
	 * @return array{
	 *  code: int,
	 *  status: string,
	 *  message: string,
	 *  jobs: array{
	 *    int: int
	 *  }
	 * } | WP_Error
	 *
	 * @throws \InvalidArgumentException
	 */
	public function create_jobs( array $params ) {
		return $this->requestWithLog(
			$this->endpoints->get_ate_jobs(),
			[
				'method' => 'POST',
				'body'   => $params,
			]
		);
	}

	/**
	 * @param int|string|array $ate_job_id
	 *
	 * @return array|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function confirm_received_job( $ate_job_id ) {
		return $this->requestWithLog( $this->endpoints->get_ate_confirm_job( $ate_job_id ) );
	}

	/**
	 * @param array|int $jobIds
	 * @param bool      $onlyFailed
	 *
	 * @return array|mixed|object|string|\WP_Error|null
	 */
	public function cancelJobs( $jobIds, $onlyFailed = false ) {
		return $this->requestWithLog(
			$this->endpoints->getAteCancelJobs(),
			[
				'method' => 'POST',
				'body'   => [
					'id' => (array) $jobIds,
					'only_failed' => $onlyFailed
				]
			]
		);
	}

	/**
	 * @param array|int $jobIds
	 * @param bool      $force
	 *
	 * @return array|mixed|object|string|\WP_Error|null
	 */
	public function hideJobs( $jobIds, $force = false ) {
		return $this->requestWithLog(
			$this->endpoints->getAteHideJobs(),
			[
				'method' => 'POST',
				'body'   => [
					'id' => (array) $jobIds,
					'force' => $force
				]
			]
		);
	}

	/**
	 * @param int    $job_id
	 * @param string $return_url
	 *
	 * @return string|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function get_editor_url( $job_id, $return_url ) {
		$lock = $this->clonedSitesHandler->checkCloneSiteLock();
		if ( $lock ) {
			return new WP_Error( 'communication_error', 'ATE communication is locked, please update configuration' );
		}

		$translator_email = filter_var( wp_get_current_user()->user_email, FILTER_SANITIZE_URL );
		$return_url = filter_var( $return_url, FILTER_SANITIZE_URL );

		$url = $this->endpoints->get_ate_editor();
		$url = str_replace(
			[
				'{job_id}',
				'{translator_email}',
				'{return_url}',
			],
			[
				$job_id,
				$translator_email ? urlencode( $translator_email ) : '',
				$return_url ? urlencode( $return_url ) : '',
			],
			$url
		);

		return $this->auth->get_signed_url_with_parameters( 'GET', $url, null );
	}

	/**
	 * @param int                          $ate_job_id
	 * @param WPML_Element_Translation_Job $job_object
	 * @param int|null $sentFrom
	 *
	 * @return array
	 */
	public function clone_job( $ate_job_id, WPML_Element_Translation_Job $job_object, $sentFrom = null ) {
		$url    = $this->endpoints->get_clone_job( $ate_job_id );
		$params = [
			'id'                  => $ate_job_id,
			'notify_url'          =>
				\WPML\TM\ATE\REST\PublicReceive::get_receive_ate_job_url( $job_object->get_id() ),
			'site_identifier'     => wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE ),
			'source_id'           => wpml_tm_get_records()
				->icl_translate_job_by_job_id( $job_object->get_id() )
				->rid(),
			'permalink'           => $job_object->get_url( true ),
			'ate_ams_console_url' => wpml_tm_get_ams_ate_console_url(),
		];

		if ( $sentFrom ) {
			$params['job_type'] = $sentFrom;
		}

		$result = $this->requestWithLog( $url, [ 'method' => 'POST', 'body' => $params ] );

		if ( ! is_object( $result ) || ! property_exists( $result, 'job_id' ) ) {
			return false;
		}

		return [
			'id'         => $result->job_id,
			'ate_status' => Obj::propOr( WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_CREATED, 'status', $result ),
		];
	}

	/**
	 * @param int $ate_job_id
	 *
	 * @return array|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function get_job( $ate_job_id ) {
		if ( ! $ate_job_id ) {
			return null;
		}

		return $this->requestWithLog( $this->endpoints->get_ate_jobs( $ate_job_id ) );
	}

	/**
	 * If `$job_ids` is not an empty array,
	 * the `$statuses` parameter will be ignored in ATE's endpoint.
	 *
	 * @see https://bitbucket.org/emartini_crossover/ate/wiki/API/V1/jobs/status
	 *
	 * @param null|array $job_ids
	 * @param null|array $statuses
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function get_jobs( $job_ids, $statuses = null ) {
		return $this->requestWithLog( $this->endpoints->get_ate_jobs( $job_ids, $statuses ) );
	}

	public function get_job_status_with_priority( $job_id ) {
		return $this->requestWithLog(
			$this->endpoints->get_ate_job_status(),
			[
				'method' => 'POST',
				'body'   => [ 'id' => $job_id,
				              'preview' => true],
			]
		);

	}

	/**
	 * @param $wpml_job_ids
	 *
	 * @return array|mixed|object|WP_Error|null
	 */
	public function get_jobs_by_wpml_ids( $wpml_job_ids ) {
		return $this->requestWithLog( $this->endpoints->get_ate_jobs_by_wpml_job_ids( $wpml_job_ids ) );
	}

	/**
	 * @param array $pairs
	 * @see https://bitbucket.org/emartini_crossover/ate/wiki/API/V1/migration/migrate
	 * @return bool
	 */
	public function migrate_source_id( array $pairs ) {
		$lock = $this->clonedSitesHandler->checkCloneSiteLock();
		if ( $lock ) {
			return false;
		}

		$verb = 'POST';

		$url = $this->auth->get_signed_url_with_parameters( $verb, $this->endpoints->get_source_id_migration(), $pairs );
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$body   = wp_json_encode( $pairs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$result = $this->wp_http->request(
			$url,
			array(
				'timeout' => 60,
				'method'  => $verb,
				'headers' => $this->json_headers(),
				'body'    => $body ?: '',
			)
		);

		if ( ! is_wp_error( $result ) ) {
			$result = $this->clonedSitesHandler->handleClonedSiteError( $result );
		}

		return $this->get_response_errors( $result ) === null;
	}

	/**
	 * @param LanguageMapping[] $languagesToMap
	 *
	 * @return Either
	 */
	public function create_language_mapping( array $languagesToMap ) {
		$result = $this->requestWithLog(
			$this->endpoints->getLanguages(),
			[
				'method' => 'POST',
				'body'   => [
					'mappings' => Fns::map( invoke( 'toATEFormat' ), Obj::values( $languagesToMap ) )
				]
			]
		);

		// it has an error when there is at least one record which has falsy "result" => "created" field
		$hasError = Lst::find( Logic::complement( Obj::path( [ 'result', 'created' ] ) ) );

		$logError = Fns::tap( function ( $data ) {
			$entry              = new Entry();
			$entry->eventType   = EventsTypes::SERVER_ATE;
			$entry->description = __( 'Saving of Language mapping to ATE failed', 'wpml-translation-management' );
			$entry->extraData   = $data;

			wpml_tm_ate_ams_log( $entry );
		} );

		return WordPress::handleError( $result )
		                ->map( Obj::prop( 'mappings' ) )
		                ->chain( Logic::ifElse( $hasError, pipe( $logError, Either::left() ), Either::right() ) );
	}

	/**
	 * @param $mappingIds
	 *
	 * @return false|array
	 */
	public function remove_language_mapping( $mappingIds ) {
		$result = $this->requestWithLog(
			$this->endpoints->getDeleteLanguagesMapping(),
			[ 'method' => 'POST', 'body' => [ 'mappings' => $mappingIds ] ]
		);

		return is_wp_error( $result ) ? false : $result;
	}

	/**
	 * @param string[] $languageCodes
	 * @param null|string $sourceLanguage
	 *
	 * @return Maybe
	 */
	public function get_languages_supported_by_automatic_translations( $languageCodes, $sourceLanguage = null ) {
		$sourceLanguage = $sourceLanguage ?: Languages::getDefaultCode();

		$result = $this->requestWithLog(
			$this->endpoints->getLanguagesCheckPairs(),
			[
				'method' => 'POST',
				'body'   => [
					[
						'source_language'  => $sourceLanguage,
						'target_languages' => $languageCodes,
					]
				]
			]
		);

		return Maybe::of( $result )
		            ->reject( 'is_wp_error' )
		            ->map( Obj::prop( 'results' ) )
		            ->map( Lst::find( Relation::propEq( 'source_language', $sourceLanguage ) ) )
		            ->map( Obj::prop( 'target_languages' ) );
	}

	/**
	 * It returns language details from ATE including the info about translation engine supporting this language.
	 *
	 *  If $inTheWebsiteContext is true, then we are taking into consideration user's translation engine settings.
	 *  It means that generally language may be supported e.g. by google, but when he turns off this engine, it will be reflected in the response.
	 *
	 * @param string $languageCode
	 * @param bool $inTheWebsiteContext
	 *
	 * @return Maybe
	 */
	public function get_language_details( $languageCode, $inTheWebsiteContext = true ) {
		$result = $this->requestWithLog( sprintf( $this->endpoints->getShowLanguage(), $languageCode ), [ 'method' => 'GET' ] );

		return Maybe::of( $result )
		            ->reject( 'is_wp_error' )
		            ->map( Obj::prop( $inTheWebsiteContext ? 'website_language' : 'language' ) );
	}

	/**
	 * @return array
	 */
	public function get_available_languages() {
		$result = $this->requestWithLog( $this->endpoints->getLanguages(), [ 'method' => 'GET' ] );

		return is_wp_error( $result ) ? [] : $result;
	}

	/**
	 * @return Maybe
	 */
	public function get_language_mapping() {
		$result = $this->requestWithLog( $this->endpoints->getLanguagesMapping(), [ 'method' => 'GET' ] );

		return Maybe::of( $result )->reject( 'is_wp_error' );
	}

	public function start_translation_memory_migration() {
		$result = $this->requestWithLog(
			$this->endpoints->startTranlsationMemoryIclMigration(),
			[
				'method' => 'POST',
				'body'   => [
					'site_identifier' => $this->get_website_id( site_url() ),
					'ts_id'           => 10,
					// random numbers for now, we should check what needs to be done for the final version.
					'ts_access_key'   => 20,
				],
			]
		);

		return WordPress::handleError( $result );
	}

	public function check_translation_memory_migration() {
		$result = $this->requestWithLog(
			$this->endpoints->checkStatusTranlsationMemoryIclMigration(),
			[
				'method' => 'GET',
				'body'   => [
					'site_identifier' => $this->get_website_id( site_url() ),
					'ts_id'           => 10,
					// random numbers for now, we should check what needs to be done for the final version.
					'ts_access_key'   => 20,
				],
			]
		);

		return WordPress::handleError( $result );
	}

	/**
	 * @see https://ate.pages.onthegosystems.com/ate-docs/ATE/API/V1/icl/translators/import
	 *
	 * @param $iclToken
	 * @param $iclServiceId
	 *
	 * @return callable|Either
	 */
	public function import_icl_translators( $tsId, $tsAccessKey ) {
		$params = [
			'site_identifier' => $this->auth->get_site_id(),
			'ts_id'           => $tsId,
			'ts_access_key'   => $tsAccessKey
		];

		$result = $this->requestWithLog( $this->endpoints->importIclTranslators(),
			[
				'method' => 'POST',
				'body'   => $params
			] );

		return WordPress::handleError( $result );
	}

	private function get_response( $result ) {
		$errors = $this->get_response_errors( $result );
		if ( is_wp_error( $errors ) ) {
			return $errors;
		}

		return $this->get_response_body( $result );
	}

	private function get_response_body( $result ) {
		if ( is_array( $result ) && array_key_exists( 'body', $result ) ) {
			$body = json_decode( $result['body'] );

			if ( isset( $body->authenticated ) && ! (bool) $body->authenticated ) {
				return new WP_Error(
					'ate_auth_failed',
					isset( $body->message ) ? $body->message : ''
				);
			}

			return $body;
		}

		return $result;
	}

	private function get_response_errors( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$response_errors = null;

		$response = (array) $response;
		if ( array_key_exists( 'body', $response ) && $response['response']['code'] >= 400 ) {
			$errors = array();

			$response_body = json_decode( $response['body'], true );

			if ( is_array( $response_body ) && array_key_exists( 'errors', $response_body ) ) {
				$errors = $response_body['errors'];
			}

			$response_errors = new WP_Error( $response['response']['code'], $response['response']['message'], $errors );
		}

		return $response_errors;
	}

	/**
	 * @return array
	 */
	private function json_headers() {
		return [
			'Accept'                                      => 'application/json',
			'Content-Type'                                => 'application/json',
			FingerprintGenerator::SITE_FINGERPRINT_HEADER => $this->fingerprintGenerator->getSiteFingerprint(),
		];
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	private function encode_body_args( array $args ) {
		return wp_json_encode( $args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * @param string $xliff_url
	 * @param array|\stdClass|false|null $job
	 *
	 * @return string
	 * @throws RequestException The request to ATE failed.
	 */
	public function get_remote_xliff_content( $xliff_url, $job = null ) {

		$avoidLogDuplication = false;
		try {
			/** @var \WP_Error|array $response */
			$response = $this->wp_http->get($xliff_url, array(
				'timeout' => min(30, ini_get('max_execution_time') ?: 10)
			));
		} catch ( \Error $e ) {
			$response = new \WP_Error(
				'ate_request_failed',
				'Started attempt to download xliff file. The process did not finish.',
				[ 'errorMessage' => $e->getMessage(), 'debugTrace' => $e->getTraceAsString() ]
			);
			$avoidLogDuplication = true;
		}

		if ( is_wp_error( $response ) ) {
			throw new RequestException(
				$response->get_error_message(),
				$response->get_error_code(),
				$response->get_error_data(),
			0,
				$avoidLogDuplication
			);
		}

		return $response['body'];
	}

	public function override_site_id( $site_id ) {
		$this->auth->override_site_id( $site_id );
	}

	public function get_website_id( $site_url ) {
		$lock = $this->clonedSitesHandler->checkCloneSiteLock();
		if ( $lock ) {
			return null;
		}

		$signed_url = $this->auth->get_signed_url_with_parameters( 'GET', $this->endpoints->get_websites() );
		if ( is_wp_error( $signed_url ) ) {
			return null;
		}

		$requestArguments = [ 'headers' => $this->json_headers() ];

		$response = $this->wp_http->request( $signed_url, $requestArguments );

		if ( ! is_wp_error( $response ) ) {
			$response = $this->clonedSitesHandler->handleClonedSiteError( $response );
		}

		$sites = $this->get_response( $response );

		foreach ( $sites as $site ) {
			if ( $site->url === $site_url ) {
				return $site->uuid;
			}
		}

		return null;
	}

	/**
	 * @param int $page
	 *
	 * @return \WPML\FP\Left|\WPML\FP\Right
	 */
	public function get_jobs_to_retranslation( int $page = 1 ) {
		try {
			$result = $this->requestWithLog(
				$this->endpoints->get_retranslate(),
				[
					'method' => 'GET',
					'body'   => [ 'page_number' => $page ],
				]
			);
		} catch ( \Exception $e ) {
			$result = new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		return WordPress::handleError( $result );
	}


	/**
	 * @see https://bitbucket.org/emartini_crossover/ate/wiki/API/V1/sync/all
	 *
	 * @param array $ateJobIds
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function sync_all( array $ateJobIds ) {
		return $this->requestWithLog(
			$this->endpoints->get_sync_all(),
			[
				'method' => 'POST',
				'body'   => [ 'ids' => $ateJobIds ],
			]
		);
	}

	/**
	 * @see https://bitbucket.org/emartini_crossover/ate/wiki/API/V1/sync/page
	 *
	 * @param string $token
	 * @param int    $page
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function sync_page( $token, $page ) {
		return $this->requestWithLog( $this->endpoints->get_sync_page( $token, $page ) );
	}

	/**
	 * @param string $url
	 * @param array  $requestArgs
	 *
	 * @return array|mixed|object|string|WP_Error|null
	 */
	private function request( $url, array $requestArgs = [] ) {
		$lock = $this->clonedSitesHandler->checkCloneSiteLock( $url );
		if ( $lock ) {
			return $lock;
		}

		$requestArgs = array_merge(
			[
				'timeout' => 60,
				'method'  => 'GET',
				'headers' => $this->json_headers(),
			],
			$requestArgs
		);

		$bodyArgs = isset( $requestArgs['body'] ) && is_array( $requestArgs['body'] )
			? $requestArgs['body'] : null;

		$signedUrl = $this->auth->get_signed_url_with_parameters( $requestArgs['method'], $url, $bodyArgs );

		if ( is_wp_error( $signedUrl ) ) {
			return $signedUrl;
		}

		// For GET requests there's no point sending parameters in the body.
		// Actually, this will trigger an error in WP_HTTP Curl class when
		// trying to build the params into a string.
		if ( $bodyArgs && $requestArgs['method'] !== 'GET' ) {
			$requestArgs['body'] = $this->encode_body_args( $bodyArgs );
		}

		$result = $this->wp_http->request( $signedUrl, $requestArgs );

		if ( ! is_wp_error( $result ) ) {
			$result = $this->clonedSitesHandler->handleClonedSiteError( $result );
		}

		return $this->get_response( $result );
	}

	/**
	 * @param string $url
	 * @param array $requestArgs
	 *
	 * @return array|int|float|object|string|WP_Error|null
	 */
	private function requestWithLog( $url, array $requestArgs = [] ) {
		$response = $this->request( $url, $requestArgs );

		if ( is_wp_error( $response ) ) {
			$entry              = new Entry();
			$entry->eventType   = EventsTypes::SERVER_ATE;
			$entry->description = $response->get_error_message();
			$errorCode = $response->get_error_code();
			$entry->extraData = [
				'url'         => $url,
				'requestArgs' => $requestArgs,
			];

            if ( $errorCode ) {
                $entry->extraData['status'] = $errorCode;
            }
			if ( $response->get_error_data( $errorCode ) ) {
				$entry->extraData['details'] = $response->get_error_data( $errorCode );
			}

			wpml_tm_ate_ams_log( $entry );

			ATEDetailedErrorMessage::saveDetailedError( $response );
		}

		return $response;
	}
}
