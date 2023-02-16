<?php

namespace WPML\TM\Container;

use WPML\TM\ATE\ClonedSites\ApiCommunication;
use WPML\TM\ATE\ClonedSites\Lock;
use WPML\TM\ATE\Log\Storage;
use WPML\TM\Notices\AteLockNotice;

class Config {

	public static function getDelegated() {
		return [
			'\WPML_Translation_Job_Factory'         => 'wpml_tm_load_job_factory',
			\WPML_TM_ATE_Job_Repository::class      => 'wpml_tm_get_ate_jobs_repository',
			\WPML_TM_Email_Notification_View::class => function () {
				$factory = new \WPML_TM_Email_Twig_Template_Factory();

				return new \WPML_TM_Email_Notification_View( $factory->create() );
			},
		];
	}

	public static function getSharedClasses() {
		return [
			'\WPML_TM_AMS_API',
			'\WPML_TM_ATE_API',
			'\WPML_TM_ATE_AMS_Endpoints',
			'\WPML_TM_ATE_Authentication',
			'\WPML_TM_AMS_ATE_Console_Section',
			'\WPML_TM_Admin_Sections',
			'\WPML_Translator_Records',
			'\WPML_Translator_Admin_Records',
			'\WPML_Translation_Manager_Records',
			'\WPML_TM_MCS_ATE_Strings',
			'\WPML_TM_AMS_Users',
			'\WPML_TM_AMS_Translator_Activation_Records',
			'\WPML_TM_REST_AMS_Clients',
			'\WPML_TM_AMS_Check_Website_ID',
			'\WPML_Translation_Job_Factory',
			\WPML_TM_Translation_Status::class,
			Storage::class,
			ApiCommunication::class,
			Lock::class,
			AteLockNotice::class,
		];
	}
}
