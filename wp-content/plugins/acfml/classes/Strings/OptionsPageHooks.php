<?php

namespace ACFML\Strings;

use ACFML\Strings\Helper\ContentTypeLabels;
use WPML\FP\Obj;

class OptionsPageHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @var Translator $translator
	 */
	private $translator;

	/**
	 * @param Factory    $factory
	 * @param Translator $translator
	 */
	public function __construct( Factory $factory, Translator $translator ) {
		$this->factory    = $factory;
		$this->translator = $translator;
	}

	public function add_hooks() {
		add_action( 'acf/update_ui_options_page', [ $this, 'register' ] );
		add_filter( 'acf/validate_options_page', [ $this, 'translate' ] );
		add_filter( 'acf/get_options_page', [ $this, 'translateMenuItems' ] );
		add_action( 'acf/delete_ui_options_page', [ $this, 'delete' ] );
	}

	/**
	 * @param array $optionsPageData
	 */
	public function register( $optionsPageData ) {
		$this->translator->registerOptionsPage( $optionsPageData );
	}

	/**
	 * @param  array $optionsPageData
	 *
	 * @return array
	 */
	public function translate( $optionsPageData ) { // phpcs:disable WordPress.WP.I18n
		return $this->translator->translateOptionsPage( $optionsPageData );
	}

	/**
	 * @param  array $optionsPageData
	 *
	 * @return array
	 */
	public function translateMenuItems( $optionsPageData ) {
		if ( ! doing_action( 'admin_menu' ) ) {
			return $optionsPageData;
		}

		if ( ! Obj::prop( 'menu_title', $optionsPageData ) ) {
			return $optionsPageData;
		}

		return array_merge(
			$optionsPageData,
			$this->translator->translateOptionsPage(
				[
					'menu_slug'  => $optionsPageData['menu_slug'],
					'menu_title' => $optionsPageData['menu_title'],
				]
			)
		);
	}

	/**
	 * @param array $optionsPageData
	 */
	public function delete( $optionsPageData ) {
		$this->factory->createPackage( $optionsPageData['menu_slug'], Package::OPTION_PAGE_PACKAGE_KIND_SLUG )->delete();
	}

}
