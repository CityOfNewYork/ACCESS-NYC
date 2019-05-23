<?php

/**
 * Class WPML_Translation_Proxy_Basket_Networking
 */
class WPML_Translation_Proxy_Basket_Networking {

	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/** @var  TranslationManagement $tm_instance */
	private $tm_instance;

	/**
	 * @param WPML_Translation_Basket $basket
	 * @param TranslationManagement   $tm_instance
	 */
	function __construct( $basket, &$tm_instance ) {
		$this->basket      = $basket;
		$this->tm_instance = $tm_instance;
	}

	/**
	 * @param WPML_TM_Translation_Batch  $batch
	 *
	 * @uses \WPML_Translation_Basket::get_basket Gets the array representation of the translation basket
	 * @uses \WPML_Translation_Proxy_Basket_Networking::generate_batch generates the batch in case no chunk was given for the commit from the basket
	 * @uses \WPML_Translation_Proxy_Basket_Networking::get_batch_name
	 * @uses \WPML_Translation_Proxy_Basket_Networking::send_all_jobs
	 * @uses \WPML_Translation_Proxy_Basket_Networking::rollback_basket_commit rolls back the whole commit action in case of an error.
	 *
	 * @return array
	 */
	function commit_basket_chunk( WPML_TM_Translation_Batch $batch ) {
		$result         = $this->send_all_jobs( $batch );
		$error_messages = $this->tm_instance->messages_by_type( 'error' );
		if ( ( $has_error = (bool) $error_messages ) === true ) {
			$this->rollback_basket_commit( $batch->get_basket_name() );
			$result['message']             = "";
			$result['additional_messages'] = $error_messages;
		}

		return array( $has_error, $result, $error_messages );
	}

	/**
	 * Cancels all remote jobs in the requested batch.
	 *
	 * @param string $posted_basket_name basket name in the currently handled request
	 *
	 * @uses \WPML_Translation_Batch::cancel_all_jobs
	 */
	function rollback_basket_commit( $posted_basket_name ) {
		$this->basket->get_basket( true );
		$basket_name = $this->basket->get_name();
		$basket_name = $basket_name ? $basket_name : $posted_basket_name;
		$batch       = $this->basket->get_basket_batch( $basket_name );
		$batch->cancel_all_jobs();
		$batch->clear_batch_data();
	}

	/**
	 * Checks if an array of translators has any remote translators in it.
	 *
	 * @param array $translators
	 *
	 * @return bool
	 */
	function contains_remote_translators( array $translators ) {

		return count( array_filter( $translators, 'is_numeric' ) ) < count( $translators );
	}

	/**
	 * Sends all jobs from basket in batch mode to translation proxy
	 *
	 * @param WPML_TM_Translation_Batch  $batch
	 * @param array  $translators
	 * @param array  $batch_options
	 *
	 * @return bool false in case of errors (read from TranslationManagement::get_messages('error') to get errors details)
	 */
	private function send_all_jobs( WPML_TM_Translation_Batch $batch) {
		$this->basket->set_options( $batch->get_batch_options() );
		$this->basket->set_name( $batch->get_basket_name() );

		$this->basket->set_remote_target_languages( $batch->get_remote_target_languages() );
		$basket_items_types = $this->basket->get_item_types();
		foreach ( $basket_items_types as $item_type_name => $item_type ) {
			do_action( 'wpml_tm_send_' . $item_type_name . '_jobs',
				$batch,
				$item_type_name
			);
		}

		// check if there were no errors
		return ! $this->tm_instance->messages_by_type( 'error' );
	}

	/**
	 * Generates the batch array for posts in the basket.
	 *
	 * @param array $basket
	 *
	 * @return array
	 */
	private function generate_batch( array $basket ) {
		$batch = array();

		$posts = isset( $basket['post'] ) ? $basket['post'] : array();
		foreach ( $posts as $post_id => $post ) {
			$batch[] = array( 'type' => 'post', 'post_id' => $post_id );
		}

		return $batch;
	}

	/**
	 * Returns the name of the batch that contains the given post_id.
	 *
	 * @param int $post_id
	 *
	 * @return null|string
	 */
	private function get_batch_name( $post_id ) {
		global $wpdb;

		$name = $wpdb->get_var( $wpdb->prepare(
			"	SELECT b.batch_name
				FROM {$wpdb->prefix}icl_translation_batches b
				JOIN {$wpdb->prefix}icl_translation_status s
					ON s.batch_id = b.id
				JOIN {$wpdb->prefix}icl_translations t
					ON t.translation_id = s.translation_id
				JOIN {$wpdb->prefix}icl_translations o
					ON o.trid = t.trid
						AND o.language_code = t.source_language_code
				JOIN {$wpdb->posts} p
					ON o.element_id = p.ID
						AND o.element_type = CONCAT('post_', p.post_type)
				WHERE o.element_id = %d
				ORDER BY b.id
				LIMIT 1",
			$post_id ) );
		$this->basket->set_name( $name );

		return $name;
	}
}