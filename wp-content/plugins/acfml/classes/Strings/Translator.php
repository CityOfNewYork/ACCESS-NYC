<?php

namespace ACFML\Strings;

use ACFML\Helper\Fields;
use ACFML\Strings\Transformer\Transformer;

class Translator {

	const CONTEXT_BACKEND  = 'backend';
	const CONTEXT_FRONTEND = 'frontend';

	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @var string $context
	 */
	private $context = self::CONTEXT_BACKEND;

	/**
	 * @param Factory $factory
	 */
	public function __construct( Factory $factory ) {
		$this->factory = $factory;
		$this->context = is_admin() ? self::CONTEXT_BACKEND : self::CONTEXT_FRONTEND;
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function registerGroupAndFieldsAndLayouts( $fieldGroup ) {
		$register = $this->factory->createRegister( $fieldGroup['key'], Package::FIELD_GROUP_PACKAGE_KIND_SLUG );

		$register->start();

		$this->factory->createFieldGroup( $fieldGroup )->traverse( $register );

		Fields::iterate(
			acf_get_fields( $fieldGroup ),
			$this->getFieldTraverser( $register ),
			$this->getLayoutTraverser( $register )
		);

		$register->end();
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return array
	 */
	public function translateGroup( $fieldGroup ) {
		return $this->factory->createFieldGroup( $fieldGroup )->traverse( $this->factory->createTranslate( $fieldGroup['key'], Package::FIELD_GROUP_PACKAGE_KIND_SLUG ), $this->context );
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public function translateField( $field ) {
		$translate = $this->factory->createTranslate( $field['parent'], Package::FIELD_GROUP_PACKAGE_KIND_SLUG );

		$wrappedField = Fields::iterate(
			[ $field ],
			$this->getFieldTraverser( $translate, $this->context ),
			$this->getLayoutTraverser( $translate, $this->context )
		);

		return $wrappedField[0];
	}

	/**
	 * @param Transformer $transformer
	 * @param string|null $context
	 *
	 * @return \Closure
	 */
	private function getFieldTraverser( $transformer, $context = null ) {
		/**
		 * @param array $field
		 *
		 * @return array
		 */
		return function( $field ) use ( $transformer, $context ) {
			return $this->factory->createField( $field )->traverse( $transformer, $context );
		};
	}

	/**
	 * @param Transformer $transformer
	 * @param string|null $context
	 *
	 * @return \Closure
	 */
	private function getLayoutTraverser( $transformer, $context = null ) {
		/**
		 * @param array $layout
		 *
		 * @return array
		 */
		return function( $layout ) use ( $transformer, $context ) {
			return $this->factory->createLayout( $layout )->traverse( $transformer, $context );
		};
	}

	/**
	 * @param array $postData
	 */
	public function registerCpt( $postData ) {
		$register = $this->factory->createRegister( $postData['post_type'], Package::CPT_PACKAGE_KIND_SLUG );

		$register->start();

		$this->factory->createCpt( $postData )->traverse( $register );

		$register->end();
	}

	/**
	 * @param  array $postData
	 * @param  array $postTypeArgs
	 *
	 * @return array
	 */
	public function translateCpt( $postData, $postTypeArgs = [] ) {
		return $this->factory->createCpt( $postData, $postTypeArgs )->traverse( $this->factory->createTranslate( $postData['post_type'], Package::CPT_PACKAGE_KIND_SLUG ), $this->context );
	}

	/**
	 * @param array $taxonomyData
	 */
	public function registerTaxonomy( $taxonomyData ) {
		$register = $this->factory->createRegister( $taxonomyData['taxonomy'], Package::TAXONOMY_PACKAGE_KIND_SLUG );

		$register->start();

		$this->factory->createTaxonomy( $taxonomyData )->traverse( $register );

		$register->end();
	}

	/**
	 * @param  array $taxonomyData
	 * @param  array $taxonomyArgs
	 *
	 * @return array
	 */
	public function translateTaxonomy( $taxonomyData, $taxonomyArgs = [] ) {
		return $this->factory->createTaxonomy( $taxonomyData, $taxonomyArgs )->traverse( $this->factory->createTranslate( $taxonomyData['taxonomy'], Package::TAXONOMY_PACKAGE_KIND_SLUG ), $this->context );
	}

	/**
	 * @param array $optionsPageData
	 */
	public function registerOptionsPage( $optionsPageData ) {
		$register = $this->factory->createRegister( $optionsPageData['menu_slug'], Package::OPTION_PAGE_PACKAGE_KIND_SLUG );

		$register->start();

		$this->factory->createOptionsPage( $optionsPageData )->traverse( $register );

		$register->end();
	}

	/**
	 * @param  array $optionsPageData
	 *
	 * @return array
	 */
	public function translateOptionsPage( $optionsPageData ) {
		return $this->factory->createOptionsPage( $optionsPageData )->traverse( $this->factory->createTranslate( $optionsPageData['menu_slug'], Package::OPTION_PAGE_PACKAGE_KIND_SLUG ), $this->context );
	}

}
