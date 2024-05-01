<?php

class WPML_Basket_Tab_Ajax {

	/** @var  TranslationProxy_Project|false $project */
	private $project;

	/** @var  WPML_Translation_Proxy_Basket_Networking $networking */
	private $networking;

	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/**
	 * @param TranslationProxy_Project|false           $project
	 * @param WPML_Translation_Proxy_Basket_Networking $networking
	 * @param WPML_Translation_Basket                  $basket
	 */
	function __construct( $project, $networking, $basket ) {
		$this->project    = $project;
		$this->networking = $networking;
		$this->basket     = $basket;
	}

	function init() {
		$request = filter_input( INPUT_POST, 'action' );
		$nonce   = filter_input( INPUT_POST, '_icl_nonce' );
		if ( $request && $nonce && wp_verify_nonce( $nonce, $request . '_nonce' ) ) {
			add_action( 'wp_ajax_send_basket_items', [ $this, 'begin_basket_commit' ] );
			add_action( 'wp_ajax_send_basket_item', [ $this, 'send_basket_chunk' ] );
			add_action( 'wp_ajax_send_basket_commit', [ $this, 'send_basket_commit' ] );
			add_action( 'wp_ajax_check_basket_name', [ $this, 'check_basket_name' ] );
			add_action( 'wp_ajax_rollback_basket', [ $this, 'rollback_basket' ] );
		}
	}

	/**
	 * Handler for the ajax call to commit a chunk of the items in a batch provided in the request.
	 *
	 * @uses \WPML_Translation_Proxy_Basket_Networking::commit_basket_chunk
	 */
	function send_basket_chunk() {
		$batch_factory = new WPML_TM_Translation_Batch_Factory( $this->basket );

		try {
			$batch                            = $batch_factory->create( $_POST );
			list( $has_error, $data, $error ) = $this->networking->commit_basket_chunk( $batch );
		} catch ( InvalidArgumentException $e ) {
			$has_error = true;
			$data      = $e->getMessage();
		}

		if ( $has_error ) {
			wp_send_json_error( $data );
		} else {
			wp_send_json_success( $data );
		}
	}

	/**
	 * Ajax handler for the first ajax request call in the basket commit workflow, responding with an message
	 * containing information about the basket's contents.
	 *
	 * @uses \WPML_Basket_Tab_Ajax::create_remote_batch_message
	 */
	function begin_basket_commit() {
		$basket_name = filter_input( INPUT_POST, 'basket_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );

		wp_send_json_success( $this->create_remote_batch_message( $basket_name ) );
	}

	/**
	 * Last ajax call in the multiple ajax calls made during the commit of a batch.
	 * Empties the basket in case the commit worked error free responds to the ajax call.
	 */
	function send_basket_commit() {
		$errors = array();
		try {
			$translators            = isset( $_POST['translators'] ) ? $_POST['translators'] : array();
			$has_remote_translators = $this->networking->contains_remote_translators( $translators );
			$response               = $this->project && $has_remote_translators ? $this->project->commit_batch_job() : true;
			$response               = ! empty( $this->project->errors ) ? false : $response;
			if ( $response !== false ) {

				if ( is_object( $response ) ) {
					$current_service = $this->project->current_service();
					if ( $current_service->redirect_to_ts ) {
						$message = sprintf(
							__(
								'You\'ve sent the content for translation to %s. Please continue to their site, to make sure that the translation starts.',
								'wpml-translation-management'
							),
							$current_service->name
						);

						$link_text = sprintf(
							__( 'Continue to %s', 'wpml-translation-management' ),
							$current_service->name
						);
					} else {
						$message = sprintf(
							__(
								'You\'ve sent the content for translation to %1$s. Currently, we are processing it and delivering to %1$s.',
								'wpml-translation-management'
							),
							$current_service->name
						);

						$link_text = __( 'Check the batch delivery status', 'wpml-translation-management' );
					}

					$response->call_to_action = $message;

					$batch_url               = OTG_TRANSLATION_PROXY_URL . sprintf( '/projects/%d/external', $this->project->get_batch_job_id() );
					$response->ts_batch_link = array(
						'href' => esc_url( $batch_url ),
						'text' => $link_text,
					);
				} elseif ( $this->contains_local_translators_different_than_current_user( $translators ) ) {
					$response           = new stdClass();
					$response->is_local = true;
				}
			}

			$errors = $response === false && $this->project ? $this->project->errors : $errors;
		} catch ( Exception $e ) {
			$response = false;
			$errors[] = $e->getMessage();
		}

		do_action( 'wpml_tm_basket_committed' );

		if ( isset( $response->is_local ) ) {
			$batch_jobs = get_option( WPML_TM_Batch_Report::BATCH_REPORT_OPTION );
			if ( $batch_jobs ) {
				$response->emails_did_not_sent = true;
			}
		}

		$this->send_json_response( $response, $errors );
	}

	/**
	 * @param $translators
	 *
	 * @return bool
	 */
	public function contains_local_translators_different_than_current_user( $translators ) {
		$is_first_available_translator = function ( $translator ) {
			return $translator === '0';
		};

		return ! \wpml_collect( $translators )
			->reject( get_current_user_id() )
			->reject( $is_first_available_translator )
			->filter(
				function ( $translator ) {
					return is_numeric( $translator );
				}
			)
			->isEmpty();
	}

	/**
	 * Ajax handler for checking if a current basket/batch name is valid for use with the currently used translation
	 * service.
	 *
	 * @uses \WPML_Translation_Basket::check_basket_name
	 */
	function check_basket_name() {
		$basket_name_max_length = TranslationProxy::get_current_service_batch_name_max_length();

		wp_send_json_success( $this->basket->check_basket_name( $this->get_basket_name(), $basket_name_max_length ) );
	}

	public function rollback_basket() {
		\WPML\TM\API\Batch::rollback( $this->get_basket_name() );
		wp_send_json_success();
	}

	/** @return string */
	private function get_basket_name() {
		return filter_input( INPUT_POST, 'basket_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
	}

	private static function sanitize_errors( $source ) {
		if ( is_array( $source ) ) {
			if ( $source && array_key_exists( 'errors', $source ) ) {
				foreach ( $source['errors'] as &$error ) {
					if ( is_array( $error ) ) {
						$error = self::sanitize_errors( $error );
					} else {
						$error = ICL_AdminNotifier::sanitize_and_format_message( $error );
					}
				}
				unset( $error );
			}
		} else {
			$source = ICL_AdminNotifier::sanitize_and_format_message( $source );
		}

		return $source;
	}

	/**
	 * Sends the response to the ajax for \WPML_Basket_Tab_Ajax::send_basket_commit and rolls back the commit
	 * in case of any errors.
	 *
	 * @see  \WPML_Basket_Tab_Ajax::send_basket_commit
	 * @uses \WPML_Translation_Basket::delete_all_items
	 *
	 * @param object|bool $response
	 * @param array       $errors
	 */
	private function send_json_response( $response, $errors ) {
		$result = array(
			'result'   => $response,
			'is_error' => ! ( $response && empty( $errors ) ),
			'errors'   => $errors,
		);
		if ( ! empty( $errors ) ) {
			\WPML\TM\API\Batch::rollback( $this->get_basket_name() );
			wp_send_json_error( self::sanitize_errors( $result ) );
		} else {
			$this->basket->delete_all_items();

			wp_send_json_success( $result );
		}
	}

	/**
	 * Creates the message that is shown before committing a batch.
	 *
	 * @see \WPML_Basket_Tab_Ajax::begin_basket_commit
	 *
	 * @param string $basket_name
	 *
	 * @return array
	 */
	private function create_remote_batch_message( $basket_name ) {
		if ( $basket_name ) {
			$this->basket->set_name( $basket_name );
		}
		$basket             = $this->basket->get_basket();
		$basket_items_types = $this->basket->get_item_types();
		if ( ! $basket ) {
			$message_content = __( 'No items found in basket', 'wpml-translation-management' );
		} else {
			$total_count             = 0;
			$message_content_details = '<ul>';
			foreach ( $basket_items_types as $item_type_name => $item_type ) {
				if ( isset( $basket[ $item_type_name ] ) ) {
					$count_item_type = count( $basket[ $item_type_name ] );
					$total_count    += $count_item_type;

					$message_content_details .= '<li>' . $item_type_name . 's: ' . $count_item_type . '</li>';
				}
			}
			$message_content_details .= '</ul>';

			$message_content  = sprintf( __( '%s items in basket:', 'wpml-translation-management' ), $total_count );
			$message_content .= $message_content_details;
		}
		$container = $message_content;

		return array(
			'message'            => $container,
			'basket'             => $basket,
			'allowed_item_types' => array_keys( $basket_items_types ),
		);
	}
}
