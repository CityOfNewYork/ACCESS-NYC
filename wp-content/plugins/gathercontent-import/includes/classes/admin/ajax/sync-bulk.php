<?php
namespace GatherContent\Importer\Admin\Ajax;
use GatherContent\Importer\General;
use GatherContent\Importer\Mapping_Post;

class Sync_Bulk extends Sync_Items {

	protected $mappings = array();
	protected $direction = 'pull';

	public function gc_pull_items_cb() {
		$this->direction = 'pull';
		$this->callback();
	}

	public function gc_push_items_cb() {
		$this->direction = 'push';
		$this->callback();
	}

	public function callback() {
		$this->verify_request();

		$this->verify_nonce();

		$this->maybe_checking_status();

		$posts = $this->set_mapping_posts();

		$this->start_sync( $posts );
	}

	protected function verify_request() {
		// Make sure we have the minimum data.
		if ( ! isset( $_REQUEST['data'], $_REQUEST['nonce'] ) ) {
			wp_send_json_error( sprintf(
				__( 'Error %d: Missing required data.', 'gathercontent-import' ),
				__LINE__
			) );
		}
	}

	protected function maybe_checking_status() {
		$data = $this->_post_val( 'data' );

		if ( empty( $data ) || empty( $data['check'] ) ) {
			return false;
		}

		$mappings = $done = array();
		foreach ( $data['check'] as $mapping_id ) {
			$mapping = Mapping_Post::get( $mapping_id, true );
			$percent = $mapping->get_sync_percent( $this->direction );
			if ( $percent > 99 ) {
				$mapping->update_items_to_sync( false, $this->direction );
			}

			if ( ! empty( $percent ) && $percent < 100 ) {
				$mappings[ $mapping_id ] = $mapping_id;
			} else {
				$done[ $mapping_id ] = $mapping_id;
			}
		}

		wp_send_json_success( array(
			'mappings'  => array_keys( $mappings ),
			'done'      => array_keys( $done ),
			'direction' => $this->direction,
		) );
	}

	protected function set_mapping_posts() {
		$data = $this->_post_val( 'data' );
		$posts = array();
		foreach ( $data as $index => $post ) {
			$mapping_id = absint( $post['mapping'] );
			try {
				$this->mappings[ $mapping_id ] = Mapping_Post::get( $mapping_id, true );
				$posts[ $mapping_id ][] = $post;
			} catch( \Exception $e ) {
				wp_send_json_error( sprintf(
					__( 'Error %d: Cannot find a mapping by that id: %d', 'gathercontent-import' ),
					__LINE__,
					$mapping_id
				) );
			}
		}
		return $posts;
	}

	protected function start_sync( $posts ) {
		foreach ( $posts as $mapping_id => $posts ) {
			$mapping = $this->mappings[ $mapping_id ];
			$items = wp_list_pluck( $posts, 'pull' === $this->direction ? 'item' : 'id' );

			// Start the sync and bump percent value.
			$mapping->update_items_to_sync( array( 'pending' => $items ), $this->direction );

			do_action( "gc_{$this->direction}_items", $mapping );
		}

		wp_send_json_success( array(
			'mappings'  => array_keys( $this->mappings ),
			'direction' => $this->direction,
		) );
	}

}
