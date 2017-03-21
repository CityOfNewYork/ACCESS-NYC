<?php
namespace GatherContent\Importer\Sync;
require_once GATHERCONTENT_INC . 'vendor/wp-async-task/wp-async-task.php';

abstract class Async_Base extends \WP_Async_Task {

	/**
	 * Launch the real postback if we don't
	 * get an exception thrown by prepare_data().
	 *
	 * @uses func_get_args() To grab any arguments passed by the action
	 */
	public function launch() {
		$data = func_get_args();
		try {
			$data = $this->prepare_data( $data );
		} catch ( Exception $e ) {
			return;
		}

		$data['action'] = "wp_async_$this->action";
		$data['_nonce'] = $this->create_async_nonce();

		$this->_body_data = $data;

		// Do not wait for shutdown hook.
		$this->launch_on_shutdown();
	}

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @throws Exception If for any reason the request should not happen
	 *
	 * @param array $data An array of data sent to the hook
	 *
	 * @return array
	 */
	protected function prepare_data( $data ){
		return array( 'mapping_id' => isset( $data[0]->ID ) ? $data[0]->ID : 0 );
	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {
		$mapping_id = absint( $_POST['mapping_id'] );
		if ( $mapping_id && ( $mapping_post = get_post( $mapping_id ) ) ) {
			do_action( "wp_async_$this->action", $mapping_post );
		}
	}
}
