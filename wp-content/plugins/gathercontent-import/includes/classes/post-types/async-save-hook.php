<?php
namespace GatherContent\Importer\Post_Types;
require_once GATHERCONTENT_INC . 'vendor/wp-async-task/wp-async-task.php';

class Async_Save_Hook extends \WP_Async_Task {
	protected $action = 'save_post';
	protected $post_type = 'save_post';

	public function __construct( $post_type ) {
		$this->post_type = $post_type;
		parent::__construct( 1 );
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
	protected function prepare_data( $data ) {
		$post_id = $data[0];
		$post = $data[1];
		if ( $this->post_type !== $post->post_type ) {
			throw new \Exception( 'We only want async tasks for: '. $this->post_type );
		}
		return array( 'post_id' => $post_id );
	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {
		$post_id = absint( $_POST['post_id'] );
		if ( $post_id && ( $post = get_post( $post_id ) ) ) {
			do_action( "wp_async_{$this->action}_{$this->post_type}", $post->ID, $post );
		}
	}
}
