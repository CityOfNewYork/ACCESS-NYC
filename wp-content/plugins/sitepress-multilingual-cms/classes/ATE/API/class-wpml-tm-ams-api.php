<?php

use WPML\TM\ATE\ClonedSites\FingerprintGenerator;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\Storage;
use WPML\TM\ATE\Log\EventsTypes;
use WPML\TM\ATE\ClonedSites\ApiCommunication as ClonedSitesHandler;
use WPML\FP\Json;
use WPML\FP\Relation;
use WPML\FP\Either;
use WPML\TM\ATE\API\ErrorMessages;
use WPML\FP\Fns;
use function WPML\FP\pipe;
use WPML\FP\Logic;
use function WPML\FP\invoke;
/**
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_API {

	const HTTP_ERROR_CODE_400 = 400;

	private $auth;
	private $endpoints;
	private $wp_http;

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
	 * @param ClonedSitesHandler         $clonedSitesHandler
	 * @param FingerprintGenerator       $fingerprintGenerator
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
	 * @param string $translator_email
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function enable_subscription( $translator_email ) {
		$result = null;

		$verb = 'PUT';
		$url  = $this->endpoints->get_enable_subscription();
		$url  = str_replace( '{translator_email}', base64_encode( $translator_email ), $url );

		$response = $this->signed_request( $verb, $url );

		if ( $this->response_has_body( $response ) ) {

			$result = $this->get_errors( $response );

			if ( ! is_wp_error( $result ) ) {
				$result = json_decode( $response['body'], true );
			}
		}

		return $result;
	}

	/**
	 * @param string $translator_email
	 *
	 * @return bool|WP_Error
	 */
	public function is_subscription_activated( $translator_email ) {
		$result = null;

		$url = $this->endpoints->get_subscription_status();

		$url = str_replace( '{translator_email}', base64_encode( $translator_email ), $url );
		$url = str_replace( '{WEBSITE_UUID}', $this->auth->get_site_id(), $url );

		$response = $this->signed_request( 'GET', $url );

		if ( $this->response_has_body( $response ) ) {

			$result = $this->get_errors( $response );

			if ( ! is_wp_error( $result ) ) {
				$result = json_decode( $response['body'], true );
				$result = $result['subscription'];
			}
		}

		return $result;
	}

	/**
	 * @return array|mixed|null|object|WP_Error
	 *
	 * @throws \InvalidArgumentException Exception.
	 */
	public function get_status() {
		$result = null;

		$registration_data = $this->get_registration_data();
		$shared            = array_key_exists( 'shared', $registration_data ) ? $registration_data['shared'] : null;

		if ( $shared ) {
			$url = $this->endpoints->get_ams_status();
			$url = str_replace( '{SHARED_KEY}', $shared, $url );

			$response = $this->request( 'GET', $url );

			if ( $this->response_has_body( $response ) ) {
				$response_body = json_decode( $response['body'], true );

				$result = $this->get_errors( $response );

				if ( ! is_wp_error( $result ) ) {
					$registration_data = $this->get_registration_data();
					if ( isset( $response_body['activated'] ) && (bool) $response_body['activated'] ) {
						$registration_data['status'] = WPML_TM_ATE_Authentication::AMS_STATUS_ACTIVE;
						$this->set_registration_data( $registration_data );
					}
					$result = $response_body;
				}
			}
		}

		return $result;
	}

	/**
	 * Used to register a manager and, at the same time, create a website in AMS.
	 * This is called only when registering the site with AMS.
	 * To register new managers or translators `\WPML_TM_ATE_AMS_Endpoints::get_ams_synchronize_managers`
	 * and `\WPML_TM_ATE_AMS_Endpoints::get_ams_synchronize_translators` will be used.
	 *
	 * @param WP_User   $manager              The WP_User instance of the manager.
	 * @param WP_User[] $translators          An array of WP_User instances representing the current translators.
	 * @param WP_User[] $managers             An array of WP_User instances representing the current managers.
	 *
	 * @return \WPML\FP\Either
	 */
	public function register_manager( WP_User $manager, array $translators, array $managers ) {
		$uuid = wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE, false );

		$makeRequest = $this->makeRegistrationRequest( $manager, $translators, $managers );

		$logErrorResponse = $this->logErrorResponse();

		$getErrors = Fns::memorize( function ( $response ) {
			return $this->get_errors( $response, false );
		} );

		$handleErrorResponse = $this->handleErrorResponse( $logErrorResponse, $getErrors );
		$handleGeneralError  = $handleErrorResponse( Fns::identity(), pipe( [ ErrorMessages::class, 'invalidResponse' ], Either::left() ) );
		$handle409Error      = $this->handle409Error( $handleErrorResponse, $makeRequest );

		return Either::of( $uuid )
		             ->chain( $makeRequest )
		             ->chain( $handle409Error )
		             ->chain( $handleGeneralError )
		             ->chain( $this->handleInvalidBodyError() )
		             ->map( $this->saveRegistrationData( $manager ) );
	}

	private function makeRegistrationRequest( $manager, $translators, $managers ) {
		$buildParams = function ( $uuid ) use ( $manager, $translators, $managers ) {
			$manager_data     = $this->get_user_data( $manager, true );
			$translators_data = $this->get_users_data( $translators );
			$managers_data    = $this->get_users_data( $managers, true );
			$sitekey          = function_exists( 'OTGS_Installer' ) ? OTGS_Installer()->get_site_key( 'wpml' ) : null;

			$params                 = $manager_data;
			$params['website_url']  = get_site_url();
			$params['website_uuid'] = $uuid;

			$params['translators']          = $translators_data;
			$params['translation_managers'] = $managers_data;
			if ( $sitekey ) {
				$params['site_key'] = $sitekey;
			}

			return $params;
		};

		$handleUnavailableATEError = function ( $response, $uuid ) {
			if ( is_wp_error( $response ) ) {
				$this->log_api_error(
					ErrorMessages::serverUnavailableHeader(),
					[ 'responseError' => $response->get_error_message(), 'website_uuid' => $uuid ]
				);
				$msg = $this->ping_healthy_wpml_endpoint() ? ErrorMessages::serverUnavailable( $uuid ) : ErrorMessages::offline( $uuid );

				return Either::left( $msg );
			}

			return Either::of( [ $response, $uuid ] );
		};

		return function ( $uuid ) use ( $buildParams, $handleUnavailableATEError ) {
			$response = $this->request( 'POST', $this->endpoints->get_ams_register_client(), $buildParams( $uuid ) );

			return $handleUnavailableATEError( $response, $uuid );
		};
	}

	private function logErrorResponse() {
		return function ( $error, $uuid ) {
			$this->log_api_error(
				ErrorMessages::respondedWithError(),
				[
					'responseError' => $error->get_error_code() === 409 ? ErrorMessages::uuidAlreadyExists() : $error->get_error_message(),
					'website_uuid'  => $uuid
				]
			);
		};
	}

	private function handleErrorResponse($logErrorResponse, $getErrors) {
		return \WPML\FP\curryN( 3, function ( $shouldHandleError, $errorHandler, $data ) use ( $logErrorResponse, $getErrors ) {
			list( $response, $uuid ) = $data;

			$error = $getErrors( $response );

			if ( $shouldHandleError( $error ) ) {
				$logErrorResponse( $error, $uuid );

				return $errorHandler( $uuid );
			}

			return Either::of( $data );
		} );
	}

	private function handle409Error($handleErrorResponse, $makeRequest) {
		$is409Error = Logic::both( Fns::identity(), pipe( invoke( 'get_error_code' ), Relation::equals( 409 ) ) );

		return $handleErrorResponse($is409Error, function ( $uuid ) use ( $makeRequest ) {
			$uuid = wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE, true );

			return $makeRequest( $uuid );
		} );
	}

	private function handleInvalidBodyError(  ) {
		return function ( $data ) {
			list( $response, $uuid ) = $data;

			if ( ! $this->response_has_keys( $response ) ) {
				$this->log_api_error(
					ErrorMessages::respondedWithError(),
					[ 'responseError' => ErrorMessages::bodyWithoutRequiredFields(), 'response' => json_encode( $response ), 'website_uuid' => $uuid ]
				);

				return Either::left( ErrorMessages::invalidResponse( $uuid ) );
			}

			return Either::of( $data );
		};
	}

	private function saveRegistrationData($manager) {
		return function ( $data ) use ( $manager ) {
			list( $response) = $data;

			$registration_data = $this->get_registration_data();

			$response_body = json_decode( $response['body'], true );

			$registration_data['user_id'] = $manager->ID;
			$registration_data['secret']  = $response_body['secret_key'];
			$registration_data['shared']  = $response_body['shared_key'];
			$registration_data['status']  = WPML_TM_ATE_Authentication::AMS_STATUS_ENABLED;

			return $this->set_registration_data( $registration_data );
		};

	}

	/**
	 * Gets the data required by AMS to register a user.
	 *
	 * @param WP_User $wp_user           The user from which data should be extracted.
	 * @param bool    $with_name_details True if name details should be included.
	 *
	 * @return array
	 */
	private function get_user_data( WP_User $wp_user, $with_name_details = false ) {
		$data = array();

		$data['email'] = $wp_user->user_email;

		if ( $with_name_details ) {
			$data['display_name'] = $wp_user->display_name;
			$data['first_name']   = $wp_user->first_name;
			$data['last_name']    = $wp_user->last_name;
		} else {
			$data['name'] = $wp_user->display_name;
		}

		return $data;
	}

	private function prepareClonedSiteArguments( $method ) {
		$headers = [
			'Accept'                                          => 'application/json',
			'Content-Type'                                    => 'application/json',
			FingerprintGenerator::NEW_SITE_FINGERPRINT_HEADER => $this->fingerprintGenerator->getSiteFingerprint(),
		];

		return [
			'method'  => $method,
			'headers' => $headers,
		];
	}

	/**
	 * @return array|WP_Error
	 */
	public function reportCopiedSite() {
		return $this->processReport(
			$this->endpoints->get_ams_site_copy(),
			'POST'
		);
	}

	/**
	 * @return array|WP_Error
	 */
	public function reportMovedSite() {
		return $this->processReport(
			$this->endpoints->get_ams_site_move(),
			'PUT'
		);
	}

	/**
	 * @param array $response Response from reportMovedSite()
	 *
	 * @return bool|WP_Error
	 */
	public function processMoveReport( $response ) {
		if ( ! $this->response_has_body( $response ) ) {
			return new WP_Error( 'auth_error', 'Unable to report site moved.' );
		}

		$response_body = json_decode( $response['body'], true );
		if ( isset( $response_body['moved_successfully'] ) && (bool) $response_body['moved_successfully'] ) {
			return true;
		}

		return new WP_Error( 'auth_error', 'Unable to report site moved.' );
	}

	/**
	 * @param array $response_body body from reportMovedSite() response.
	 *
	 * @return bool
	 */
	private function storeAuthData( $response_body ) {
		$setRegistrationDataResult = $this->updateRegistrationData( $response_body );
		$setUuidResult             = $this->updateSiteUuId( $response_body );

		return $setRegistrationDataResult && $setUuidResult;
	}

	/**
	 * @param array $response_body body from reportMovedSite() response.
	 *
	 * @return bool
	 */
	private function updateRegistrationData( $response_body ) {
		$registration_data = $this->get_registration_data();

		$registration_data['secret'] = $response_body['new_secret_key'];
		$registration_data['shared'] = $response_body['new_shared_key'];

		return $this->set_registration_data( $registration_data );
	}

	/**
	 * @param array $response_body body from reportMovedSite() response.
	 *
	 * @return bool
	 */
	private function updateSiteUuId( $response_body ) {
		$this->override_site_id( $response_body['new_website_uuid'] );

		return update_option(
			WPML_Site_ID::SITE_ID_KEY . ':ate',
			$response_body['new_website_uuid'],
			false
		);
	}

	private function sendSiteReportConfirmation() {
		$url    = $this->endpoints->get_ams_site_confirm();
		$method = 'POST';

		$args = $this->prepareClonedSiteArguments( $method );

		$url_parts = wp_parse_url( $url );

		$registration_data         = $this->get_registration_data();
		$query['new_shared_key']   = $registration_data['shared'];
		$query['token']            = uuid_v5( wp_generate_uuid4(), $url );
		$query['new_website_uuid'] = $this->auth->get_site_id();
		$url_parts['query']        = http_build_query( $query );

		$url = http_build_url( $url_parts );

		$signed_url = $this->auth->signUrl( $method, $url );

		$response = $this->wp_http->request( $signed_url, $args );

		if ( $this->response_has_body( $response ) ) {
			$response_body = json_decode( $response['body'], true );

			return (bool) $response_body['confirmed'];
		}

		return new WP_Error( 'auth_error', 'Unable confirm site copied.' );
	}

	/**
	 * @param string $url
	 * @param string $method
	 *
	 * @return array|WP_Error
	 */
	private function processReport( $url, $method ) {
		$args = $this->prepareClonedSiteArguments( $method );

		$url_parts = wp_parse_url( $url );

		$registration_data     = $this->get_registration_data();
		$query['shared_key']   = $registration_data['shared'];
		$query['token']        = uuid_v5( wp_generate_uuid4(), $url );
		$query['website_uuid'] = $this->auth->get_site_id();
		$url_parts['query']    = http_build_query( $query );

		$url = http_build_url( $url_parts );

		$signed_url = $this->auth->signUrl( $method, $url );

		return $this->wp_http->request( $signed_url, $args );
	}

	/**
	 * @param array $response Response from reportCopiedSite()
	 *
	 * @return bool
	 */
	public function processCopyReportConfirmation( $response ) {
		if ( $this->response_has_body( $response ) ) {
			$response_body = json_decode( $response['body'], true );

			return $this->storeAuthData( $response_body ) && (bool) $this->sendSiteReportConfirmation();
		}

		return false;
	}

	/**
	 * Converts an array of WP_User instances into an array of data nedded by AMS to identify users.
	 *
	 * @param WP_User[] $users             An array of WP_User instances.
	 * @param bool      $with_name_details True if name details should be included.
	 *
	 * @return array
	 */
	private function get_users_data( array $users, $with_name_details = false ) {
		$user_data = array();

		foreach ( $users as $user ) {
			$wp_user     = get_user_by( 'id', $user->ID );
			$user_data[] = $this->get_user_data( $wp_user, $with_name_details );
		}

		return $user_data;
	}

	/**
	 * Checks if a reponse has a body.
	 *
	 * @param array|\WP_Error $response The response of the remote request.
	 *
	 * @return bool
	 */
	private function response_has_body( $response ) {
		return ! is_wp_error( $response ) && array_key_exists( 'body', $response );
	}

	private function get_errors( $response, $logError = true ) {
		$response_errors = null;

		if ( is_wp_error( $response ) ) {
			$response_errors = $response;
		} elseif ( array_key_exists( 'body', $response ) && $response['response']['code'] >= self::HTTP_ERROR_CODE_400 ) {
			$main_error    = array();
			$errors        = array();
			$error_message = $response['response']['message'];

			$response_body = json_decode( $response['body'], true );
			if ( ! $response_body ) {
				$error_message = $response['body'];
				$main_error    = array( $response['body'] );
			} elseif ( array_key_exists( 'errors', $response_body ) ) {
				$errors        = $response_body['errors'];
				$main_error    = array_shift( $errors );
				$error_message = $this->get_error_message( $main_error, $response['body'] );
			}

			$response_errors = new WP_Error( $response['response']['code'], $error_message, $main_error );

			foreach ( $errors as $error ) {
				$error_message = $this->get_error_message( $error, $response['body'] );
				$error_status  = isset( $error['status'] ) ? 'ams_error: ' . $error['status'] : '';
				$response_errors->add( $error_status, $error_message, $error );
			}
		}

		if ( $logError && $response_errors ) {
			$this->log_api_error( $response_errors->get_error_message(), $response_errors->get_error_data() );
		}

		return $response_errors;
	}

	private function log_api_error( $message, $data ) {
		$entry              = new Entry();
		$entry->eventType   = EventsTypes::SERVER_AMS;
		$entry->description = $message;
		$entry->extraData   = [ 'errorData' => $data ];

		wpml_tm_ate_ams_log( $entry );
	}

	private function ping_healthy_wpml_endpoint() {
		$response = $this->request( 'GET', defined( 'WPML_TM_INTERNET_CHECK_URL' ) ? WPML_TM_INTERNET_CHECK_URL : 'https://health.wpml.org/', [] );

		return ! is_wp_error( $response ) && (int) \WPML\FP\Obj::path( [ 'response', 'code' ], $response ) === 200;
	}

	/**
	 * @param array  $ams_error
	 * @param string $default
	 *
	 * @return string
	 */
	private function get_error_message( $ams_error, $default ) {
		$title   = isset( $ams_error['title'] ) ? $ams_error['title'] . ': ' : '';
		$details = isset( $ams_error['detail'] ) ? $ams_error['detail'] : $default;

		return $title . $details;
	}

	private function response_has_keys( $response ) {
		$response_body = json_decode( $response['body'], true );

		return array_key_exists( 'secret_key', $response_body ) && array_key_exists( 'shared_key', $response_body );
	}

	/**
	 * @return array
	 */
	public function get_registration_data() {
		return get_option( WPML_TM_ATE_Authentication::AMS_DATA_KEY, [] );
	}

	/**
	 * @param $registration_data
	 *
	 * @return bool
	 */
	private function set_registration_data( $registration_data ) {
		return update_option( WPML_TM_ATE_Authentication::AMS_DATA_KEY, $registration_data );
	}

	/**
	 * @param array $managers
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function synchronize_managers( array $managers ) {
		$result = null;

		$managers_data = $this->get_users_data( $managers, true );

		if ( $managers_data ) {
			$url = $this->endpoints->get_ams_synchronize_managers();
			$url = str_replace( '{WEBSITE_UUID}', wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE ), $url );

			$params = array( 'translation_managers' => $managers_data );

			$response = $this->signed_request( 'PUT', $url, $params );

			if ( $this->response_has_body( $response ) ) {
				$response_body = json_decode( $response['body'], true );

				$result = $this->get_errors( $response );

				if ( ! is_wp_error( $result ) ) {
					$result = $response_body;
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $translators
	 *
	 * @return array|mixed|null|object|WP_Error
	 * @throws \InvalidArgumentException
	 */
	public function synchronize_translators( array $translators ) {
		$result = null;

		$translators_data = $this->get_users_data( $translators );

		if ( $translators_data ) {
			$url = $this->endpoints->get_ams_synchronize_translators();

			$params = array( 'translators' => $translators_data );

			$response = $this->signed_request( 'PUT', $url, $params );

			if ( $this->response_has_body( $response ) ) {
				$response_body = json_decode( $response['body'], true );

				$result = $this->get_errors( $response );

				if ( ! is_wp_error( $result ) ) {
					$result = $response_body;
				}
			} elseif ( is_wp_error( $response ) ) {
				$result = $response;
			}
		}

		return $result;
	}

	/**
	 * @param string     $method
	 * @param string     $url
	 * @param array|null $params
	 *
	 * @return array|WP_Error
	 */
	private function request( $method, $url, array $params = null ) {
		$lock = $this->clonedSitesHandler->checkCloneSiteLock();
		if ( $lock ) {
			return $lock;
		}

		$method  = strtoupper( $method );
		$headers = [
			'Accept'                                      => 'application/json',
			'Content-Type'                                => 'application/json',
			FingerprintGenerator::SITE_FINGERPRINT_HEADER => $this->fingerprintGenerator->getSiteFingerprint(),
		];

		$args = [
			'method'  => $method,
			'headers' => $headers,
			'timeout' => max( ini_get( 'max_execution_time' ) / 2, 5 ),
		];

		if ( $params ) {
			$args['body'] = wp_json_encode( $params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		$response = $this->wp_http->request( $this->add_versions_to_url( $url ), $args );

		if ( ! is_wp_error( $response ) ) {
			$response = $this->clonedSitesHandler->handleClonedSiteError( $response );
		}

		return $response;
	}

	/**
	 * @param string     $verb
	 * @param string     $url
	 * @param array|null $params
	 *
	 * @return array|WP_Error
	 */
	private function signed_request( $verb, $url, array $params = null ) {
		$verb       = strtoupper( $verb );
		$signed_url = $this->auth->get_signed_url_with_parameters( $verb, $url, $params );

		if ( is_wp_error( $signed_url ) ) {
			return $signed_url;
		}

		return $this->request( $verb, $signed_url, $params );
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	private function add_versions_to_url( $url ) {
		$url_parts = wp_parse_url( $url );
		$query     = array();
		if ( array_key_exists( 'query', $url_parts ) ) {
			parse_str( $url_parts['query'], $query );
		}
		$query['wpml_core_version'] = ICL_SITEPRESS_VERSION;
		$query['wpml_tm_version']   = WPML_TM_VERSION;

		$url_parts['query'] = http_build_query( $query );
		$url                = http_build_url( $url_parts );

		return $url;
	}

	public function override_site_id( $site_id ) {
		$this->auth->override_site_id( $site_id );
	}

	/**
	 * @return array|WP_Error
	 */
	public function getCredits() {
		return $this->getSignedResult(
			'GET',
			$this->endpoints->get_credits()
		);
	}

	/**
	 * @return array|WP_Error
	 */
	public function resumeAll() {
		return $this->getSignedResult(
			'GET',
			$this->endpoints->get_resume_all()
		);
	}

	public function send_sitekey( $sitekey ) {
		$siteId   = wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE );
		$response = $this->getSignedResult(
			'POST',
			$this->endpoints->get_send_sitekey(),
			[
				'site_key'     => $sitekey,
				'website_uuid' => $siteId,
			]
		);

		return Relation::propEq( 'updated_website', $siteId, $response );
	}

	/**
	 * @param string     $verb
	 * @param string     $url
	 * @param array|null $params
	 *
	 * @return array|WP_Error
	 */
	private function getSignedResult( $verb, $url, array $params = null ) {
		$result = null;

		$response = $this->signed_request( $verb, $url, $params );

		if ( $this->response_has_body( $response ) ) {

			$result = $this->get_errors( $response );

			if ( ! is_wp_error( $result ) ) {
				$result = Json::toArray( $response['body'] );
			}
		}

		return $result;

	}
}
