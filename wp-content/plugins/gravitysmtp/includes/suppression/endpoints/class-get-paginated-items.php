<?php

namespace Gravity_Forms\Gravity_SMTP\Suppression\Endpoints;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Suppressed_Emails_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Attachments_Saver;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Get_Paginated_Items extends Endpoint {

	const ACTION_NAME = 'suppressed_emails_page';

	const PARAM_PER_PAGE       = 'per_page';
	const PARAM_REQUESTED_PAGE = 'requested_page';
	const PARAM_SEARCH_TERM    = 'search_term';

	/**
	 * @var Suppressed_Emails_Model
	 */
	protected $emails;

	public function __construct( $suppressed_emails_model ) {
		$this->emails = $suppressed_emails_model;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$per_page       = filter_input( INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT );
		$requested_page = filter_input( INPUT_POST, 'requested_page', FILTER_SANITIZE_NUMBER_INT );
		$search_term    = filter_input( INPUT_POST, 'search_term' );

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		$requested_page = intval( $requested_page );
		$offset         = ( $requested_page - 1 ) * $per_page;

		if ( empty( $per_page ) ) {
			$per_page = 20;
		}

		$rows              = $this->emails->paginate( $requested_page, $per_page, $search_term );
		$count             = $this->emails->count( $search_term );

		$data = array(
			'rows'      => $this->get_suppression_data_formatted_as_rows( $rows ),
			'total'     => $count,
			'row_count' => count( $rows ),
		);

		wp_send_json_success( $data );
	}

	private function get_suppression_data_formatted_as_rows( $data ) {
		return $this->emails->format_as_data_rows( $data );
	}


}
