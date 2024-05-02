<?php

namespace ACFML\Strings;

use ACFML\Strings\Helper\ContentTypeLabels;

class TaxonomyHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

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
		add_action( 'acf/update_taxonomy', [ $this, 'register' ] );
		add_filter( 'acf/taxonomy/registration_args', [ $this, 'translate' ], 10, 2 );
		add_action( 'acf/delete_taxonomy', [ $this, 'delete' ] );
	}

	/**
	 * @param array $taxonomyData
	 */
	public function register( $taxonomyData ) {
		$this->translator->registerTaxonomy( $taxonomyData );
	}

	/**
	 * @param  array $taxonomyArgs
	 * @param  array $taxonomyData
	 *
	 * @return array
	 */
	public function translate( $taxonomyArgs, $taxonomyData ) { // phpcs:disable WordPress.WP.I18n
		return ContentTypeLabels::translateLabels(
			$taxonomyArgs,
			$this->translator->translateTaxonomy( $taxonomyData, $taxonomyArgs ),
			[ 'description' ]
		);
	}

	/**
	 * @param array $taxonomyData
	 */
	public function delete( $taxonomyData ) {
		$this->factory->createPackage( $taxonomyData['taxonomy'], Package::TAXONOMY_PACKAGE_KIND_SLUG )->delete();
	}
}
