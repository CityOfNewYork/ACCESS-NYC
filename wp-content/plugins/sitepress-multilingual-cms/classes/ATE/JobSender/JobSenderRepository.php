<?php
namespace WPML\TM\ATE\JobSender;

class JobSenderRepository {

	/**
	 * @return \WPML_TM_ATE_Models_Job_Sender
	 */
	public static function get() {
		$currentUser = wp_get_current_user();

		return new \WPML_TM_ATE_Models_Job_Sender(
			$currentUser->ID,
			$currentUser->user_email,
			$currentUser->user_login,
			$currentUser->display_name
		);
	}


}
