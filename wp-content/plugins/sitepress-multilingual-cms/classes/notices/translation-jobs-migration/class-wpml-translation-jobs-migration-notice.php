<?php

abstract class WPML_Translation_Jobs_Migration_Notice {

	const NOTICE_GROUP_ID = 'translation-jobs';
	const TEMPLATE        = 'translation-jobs-migration.twig';

	/**
	 * The instance of \WPML_Notices.
	 *
	 * @var \WPML_Notices
	 */
	private $admin_notices;

	/**
	 * The instance of \IWPML_Template_Service.
	 *
	 * @var \IWPML_Template_Service
	 */
	private $template_service;

	/**
	 * WPML_Translation_Jobs_Migration_Notice constructor.
	 *
	 * @param \WPML_Notices           $admin_notices    An instance of \WPML_Notices.
	 * @param \IWPML_Template_Service $template_service A class implementing \IWPML_Template_Service.
	 */
	public function __construct( WPML_Notices $admin_notices, IWPML_Template_Service $template_service ) {
		$this->admin_notices    = $admin_notices;
		$this->template_service = $template_service;
	}

	/**
	 * It adds the notice to be shown when conditions meet.
	 */
	public function add_notice() {
		$notice = $this->admin_notices->create_notice( $this->get_notice_id(), $this->get_notice_content(), self::NOTICE_GROUP_ID );
		$notice->set_css_class_types( 'notice-error' );
		$this->admin_notices->add_notice( $notice );
	}

	/**
	 * It removes the notice.
	 */
	public function remove_notice() {
		$this->admin_notices->remove_notice( self::NOTICE_GROUP_ID, $this->get_notice_id() );
	}

	/**
	 * It checks is the notice exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return (bool) $this->admin_notices->get_notice( $this->get_notice_id(), self::NOTICE_GROUP_ID );
	}

	/**
	 * It gets the notice content.
	 *
	 * @return string
	 */
	private function get_notice_content() {
		return $this->template_service->show( $this->get_model(), self::TEMPLATE );
	}

	/**
	 * It gets the definition of the notice's content.
	 *
	 * @return array
	 */
	abstract protected function get_model();

	/**
	 * It gets the ID of the notice.
	 *
	 * @return string
	 */
	abstract protected function get_notice_id();
}
