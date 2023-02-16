<?php

namespace WPML\TM\TranslationProxy\Services\Project;

class SiteDetails {
	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @return string
	 */
	public function getDeliveryMethod() {
		return (int) $this->sitepress->get_setting( 'translation_pickup_method' ) === ICL_PRO_TRANSLATION_PICKUP_XMLRPC
			? 'xmlrpc'
			: 'polling';
	}

	/**
	 * @return array
	 */
	public function getBlogInfo() {
		return [
			'url'         => get_option( 'siteurl' ),
			'name'        => get_option( 'blogname' ),
			'description' => get_option( 'blogdescription' ),
		];
	}

	/**
	 * @return array
	 */
	public function getClientData() {
		$current_user = wp_get_current_user();

		return [
			'email' => $current_user->user_email,
			'name'  => $current_user->display_name,
		];
	}
}
