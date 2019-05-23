<?php

class WPML_TM_Jobs_List_Status_Names {
	/**
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			ICL_TM_NOT_TRANSLATED                => __( 'Not translated', 'wpml-translation-management' ),
			ICL_TM_WAITING_FOR_TRANSLATOR        => __( 'Waiting for translator', 'wpml-translation-management' ),
			ICL_TM_IN_PROGRESS                   => __( 'In progress', 'wpml-translation-management' ),
			ICL_TM_NEEDS_UPDATE                  => __( 'Needs update', 'wpml-translation-management' ),
			ICL_TM_TRANSLATION_READY_TO_DOWNLOAD => __( 'Translation ready to download',
				'wpml-translation-management' ),
			ICL_TM_COMPLETE                      => __( 'Completed', 'wpml-translation-management' ),
		);
	}
}