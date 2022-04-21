<?php

namespace WPML\TM\TranslationProxy\Services;

class Storage {
	/** @var  \SitePress $sitepress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Gets the current translation service
	 *
	 * @return bool|\stdClass
	 */
	public function getCurrentService() {
		return $this->sitepress->get_setting( 'translation_service' );
	}

	/**
	 * Saves the input service as the current translation service setting.
	 *
	 * @param \stdClass $service
	 */
	public function setCurrentService( \stdClass $service ) {
		do_action( 'wpml_tm_before_set_translation_service', $service );
		$this->sitepress->set_setting( 'translation_service', $service, true );
	}
}
