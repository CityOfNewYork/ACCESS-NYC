<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\General;
use GatherContent\Importer\Mapping_Post;

class Sync_Items extends Plugin_Base {

	protected $mapping = null;

	public function gc_sync_items_cb() {

		$this->verify_request();

		$this->verify_nonce();

		$this->set_mapping_post();

		$this->maybe_cancelling();

		$this->maybe_checking_status();

		$fields = $this->get_fields();

		$this->start_pull( $fields );
	}

	protected function verify_request() {
		// Make sure we have the minimum data.
		if ( ! isset( $_REQUEST['data'], $_REQUEST['id'], $_REQUEST['nonce'] ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing required data.', 'gathercontent-import' ),
				__LINE__
			) );
		}
	}

	protected function verify_nonce() {
		// Get opt-group for nonce-verification
		$opt_group = General::get_instance()->admin->mapping_wizard->option_group;

 		// No nonce, no pass.
		if ( ! wp_verify_nonce( $this->_post_val( 'nonce' ), $opt_group . '-options' ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing security nonce.', 'gathercontent-import' ),
				__LINE__
			) );
		}
	}

	protected function set_mapping_post() {
		try {
			$this->mapping = Mapping_Post::get( absint( $this->_post_val( 'id' ) ), true );
		} catch( \Exception $e ) {
			$this->maybe_cancelling();

			wp_send_json_error( sprintf(
				__( 'Error %d: Cannot find a mapping by that id: %d', 'gathercontent-import' ),
				__LINE__,
				absint( $this->_post_val( 'id' ) )
			) );
		}

	}

	protected function maybe_cancelling() {
		if ( 'cancel' !== $this->_post_val( 'data' ) ) {
			return false;
		}

		if ( $this->mapping ) {
			$this->mapping->update_items_to_pull( false );
		}

		wp_send_json_success();
	}

	protected function maybe_checking_status() {
		if ( 'check' !== $this->_post_val( 'data' ) ) {
			return false;
		}

		$percent = $this->mapping->get_pull_percent();

		// do_action( 'gc_pull_items', $this->mapping );

		wp_send_json_success( compact( 'percent' ) );
	}

	protected function get_fields() {
		$data = $this->_post_val( 'data' );

		if ( empty( $data ) || ! is_string( $data ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing form data.', 'gathercontent-import' ),
				__LINE__
			) );
		}

		// Parse the serialized fields string.
		parse_str( $data, $fields );

		if (
			! isset( $fields['import'], $fields['project'], $fields['template'] )
			|| empty( $fields['import'] ) || ! is_array( $fields['import'] )
			|| $this->mapping->get_project() != $fields['project']
			|| $this->mapping->get_template() != $fields['template']
		) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing required form data.', 'gathercontent-import' ),
				__LINE__
			) );
		}

		$fields['project']  = absint( $fields['project'] );
		$fields['template'] = absint( $fields['template'] );
		$fields['import']   = array_map( 'absint', $fields['import'] );

		return $fields;
	}

	protected function start_pull( $fields ) {

		// Start the sync and bump percent value.
		$this->mapping->update_items_to_pull( array( 'pending' => $fields['import'] ) );

		do_action( 'gc_pull_items', $this->mapping );

		$percent = 0.1;

		wp_send_json_success( compact( 'percent' ) );
	}

}
