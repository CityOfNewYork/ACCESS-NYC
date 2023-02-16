<?php

class WPML_All_Translation_Jobs_Migration_Notice extends WPML_Translation_Jobs_Migration_Notice {

	/**
	 * It gets the definition of the notice's content.
	 *
	 * @return array
	 */
	protected function get_model() {
		return array(
			'strings' => array(
				'title'              => __( 'Problem receiving translation jobs?', 'wpml-translation-management' ),
				'description'        => __( 'WPML needs to update its table of translation jobs, so that your site can continue receiving completed translations. This process will take a few minutes and does not modify content or translations in your site.', 'wpml-translation-management' ),
				'button'             => __( 'Start update', 'wpml-translation-management' ),
				/* translators: this is shown between two number: processed items and total number of items to process */
				'of'                 => __( 'of', 'wpml-translation-management' ),
				'jobs_migrated'      => __( 'jobs fixed', 'wpml-translation-management' ),
				'communicationError' => __(
					'The communication error with Translation Proxy has appeared. Please try later.',
					'wpml-translation-management'
				),
			),
			'nonce'   => wp_nonce_field(
				WPML_Translation_Jobs_Migration_Ajax::ACTION,
				WPML_Translation_Jobs_Migration_Ajax::ACTION,
				false,
				false
			),
		);
	}

	/**
	 * It gets the ID of the notice.
	 *
	 * @return string
	 */
	protected function get_notice_id() {
		return 'all-translation-jobs-migration';
	}
}
