<?php

class WPML_TM_TS_Instructions_Notice {
	const NOTICE_ID       = 'translation-service-instructions';
	const NOTICE_GROUP_ID = 'translation-service-instructions';
	const TEMPLATE        = 'translation-service-instructions.twig';

	/** @var WPML_Notices */
	private $admin_notices;

	/** @var IWPML_Template_Service */
	private $template_service;

	public function __construct( WPML_Notices $admin_notices, IWPML_Template_Service $template_service ) {
		$this->admin_notices    = $admin_notices;
		$this->template_service = $template_service;
	}

	/**
	 * @param stdClass $service
	 */
	public function add_notice( $service ) {
		$notice = $this->admin_notices->create_notice(
			self::NOTICE_ID,
			$this->get_notice_content( $service ),
			self::NOTICE_GROUP_ID
		);
		$notice->add_display_callback( array( 'WPML_TM_Page', 'is_tm_dashboard' ) );
		$notice->set_text_only( true );
		$this->admin_notices->add_notice( $notice );
	}

	public function remove_notice() {
		$this->admin_notices->remove_notice( self::NOTICE_GROUP_ID, self::NOTICE_ID );
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return ( WPML_TM_Page::is_dashboard() || wpml_is_ajax() ) &&
		       (bool) $this->admin_notices->get_notice( self::NOTICE_ID, self::NOTICE_GROUP_ID );
	}

	/**
	 * @param stdClass $service
	 *
	 * @return string
	 */
	private function get_notice_content( $service ) {
		$model = $this->get_model( $service );

		return $this->template_service->show( $model, self::TEMPLATE );
	}

	/**
	 * @param stdClass $service
	 *
	 * @return array
	 */
	private function get_model( $service ) {
		return array(
			'strings'    => array(
				'title'                         => sprintf(
					__( 'How to work correctly with %s', 'wpml-translation-management' ),
					$service->name
				),
				'description'                   => sprintf(
					__( "Congratulations for choosing %s to translate your site's content. To avoid high costs and wasted time, please watch our short video.",
						'wpml-translation-management' ),
					$service->name
				),
				'need_help'                     => __( 'Need help? See ', 'wpml-translation-management' ),
				'help_caption'                  => __(
					'how to translate different parts of the site.',
					'wpml-translation-management'
				),
				'this_stuff_is_important'       => __( 'This stuff is actually important. Please follow the video to send a test translation. Then, you can dismiss this message. Thank you!', 'wpml-translation-management' ),
				'my_test_translation_went_fine' => __( 'My test translation went fine.', 'wpml-translation-management' ),
				'dismiss'                       => __( 'Dismiss this message.', 'wpml-translation-management' ),
			),
			'image_url'  => WPML_TM_URL . '/res/img/ts-instruction-video.png',
			'help_link'  => 'https://wpml.org/documentation/translating-your-contents/professional-translation-via-wpml/doing-test-translation/?utm_source=wpmlplugin&utm_campaign=translation-services&utm_medium=translation-dashboard-message&utm_term=doing-test-translation',
			'video_link' => 'https://wpml.org/documentation/translating-your-contents/professional-translation-via-wpml/doing-test-translation/?utm_source=wpmlplugin&utm_medium=translation-dashboard-message-video&utm_campaign=translation-services&utm_term=doing-test-translation'
		);
	}
}
