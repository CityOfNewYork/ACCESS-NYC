<?php

class WPML_TM_Translation_Jobs_Fix_Summary_Notice extends WPML_Translation_Jobs_Migration_Notice {

	protected function get_model() {
		$tm_url      = '<a href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' ) . '">' . __( 'Translation Dashboard', 'sitepress' ) . '</a>';
		$description = sprintf( __( 'WPML completed updating the translations history. Next, please visit the %s and click on the button to "Check status and get translations".', 'wpml-translation-management' ), $tm_url );

		return array(
			'strings' => array(
				'title'       => __( 'Problem receiving translation jobs?', 'sitepress' ),
				'description' => $description,
			),
		);
	}

	protected function get_notice_id() {
		return 'translation-jobs-migration-fix-summary';
	}
}