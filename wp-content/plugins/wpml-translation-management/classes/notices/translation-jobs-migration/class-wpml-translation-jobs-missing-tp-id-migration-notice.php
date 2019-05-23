<?php

class WPML_Translation_Jobs_Missing_TP_ID_Migration_Notice extends WPML_Translation_Jobs_Migration_Notice {

	/**
	 * It gets the definition of the notice's content.
	 *
	 * @return array
	 */
	protected function get_model() {
		return array(
			'strings' => array(
				'title'         => __( 'WPML Translation Jobs Migration', 'wpml-translation-management' ),
				/* translators: the placeholder is replaced with the current version of WPML */
				'description'   => sprintf( __( 'WPML found some remote jobs on your site that must be migrated in order to work with WPML %s. You might not be able to access some of the WPML administration pages until this migration is fully completed.', 'wpml-translation-management' ), ICL_SITEPRESS_VERSION ),
				'button'        => __( 'Run now', 'wpml-translation-management' ),
				/* translators: this is shown between two number: processed items and total number of items to process */
				'of'            => __( 'of', 'wpml-translation-management' ),
				'jobs_migrated' => __( 'jobs migrated', 'wpml-translation-management' ),
				'communicationError' => __(
					'The communication error with Translation Proxy has appeared. Please try later.',
					'wpml-translation-management'
				),
			),
			'nonce'   => wp_nonce_field( WPML_Translation_Jobs_Migration_Ajax::ACTION, WPML_Translation_Jobs_Migration_Ajax::ACTION, false, false ),
		);
	}

	/**
	 * It gets the ID of the notice.
	 *
	 * @return string
	 */
	protected function get_notice_id() {
		return 'translation-jobs-migration';
	}
}
