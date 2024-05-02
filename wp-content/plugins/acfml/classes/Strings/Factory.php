<?php

namespace ACFML\Strings;

use ACFML\Strings\Transformer\Register;
use ACFML\Strings\Transformer\Translate;
use ACFML\Strings\Traversable\Cpt;
use ACFML\Strings\Traversable\FieldGroup;
use ACFML\Strings\Traversable\Field;
use ACFML\Strings\Traversable\Layout;
use ACFML\Strings\Traversable\OptionsPage;
use ACFML\Strings\Traversable\Taxonomy;
use ACFML\Helper\FieldGroup as GroupHelper;

class Factory {

	/**
	 * @param array $fieldGroup
	 *
	 * @return FieldGroup
	 */
	public function createFieldGroup( $fieldGroup ) {
		return new FieldGroup( $fieldGroup );
	}

	/**
	 * @param array $field
	 *
	 * @return Field
	 */
	public function createField( $field ) {
		return new Field( $field );
	}

	/**
	 * @param array $layout
	 *
	 * @return Layout
	 */
	public function createLayout( $layout ) {
		return new Layout( $layout );
	}

	/**
	 * @param array $data
	 * @param array $context
	 *
	 * @return Cpt
	 */
	public function createCpt( $data, $context = [] ) {
		return new Cpt( $data, $context );
	}

	/**
	 * @param array $data
	 * @param array $context
	 *
	 * @return Taxonomy
	 */
	public function createTaxonomy( $data, $context = [] ) {
		return new Taxonomy( $data, $context );
	}

	/**
	 * @param array $data
	 *
	 * @return OptionsPage
	 */
	public function createOptionsPage( $data ) {
		return new OptionsPage( $data );
	}

	/**
	 * @param string|int $packageId
	 * @param string     $kind
	 *
	 * @return Package
	 */
	public function createPackage( $packageId, $kind = Package::FIELD_GROUP_PACKAGE_KIND_SLUG ) {
		return new Package( $packageId, $kind );
	}

	/**
	 * @param  int|string $id
	 * @param  string     $kind
	 *
	 * @return Register
	 */
	public function createRegister( $id, $kind = Package::FIELD_GROUP_PACKAGE_KIND_SLUG ) {
		if ( Package::FIELD_GROUP_PACKAGE_KIND_SLUG === $kind ) {
			return new Register(
				$this->createPackage( GroupHelper::getId( $id ), $kind )
			);
		}
		return new Register(
			$this->createPackage( $id, $kind )
		);
	}

	/**
	 * @param  int|string $id
	 * @param  string     $kind
	 *
	 * @return Translate
	 */
	public function createTranslate( $id, $kind = Package::FIELD_GROUP_PACKAGE_KIND_SLUG ) {
		if ( Package::FIELD_GROUP_PACKAGE_KIND_SLUG === $kind ) {
			return new Translate(
				$this->createPackage( GroupHelper::getId( $id ), $kind )
			);
		}
		return new Translate(
			$this->createPackage( $id, $kind )
		);
	}

	/**
	 * @return TranslationJobFilter
	 */
	public function createTranslationJobFilter() {
		return new TranslationJobFilter( $this );
	}

	/**
	 * @param \stdClass|\WPML_Package|array|int $data
	 *
	 * @return \WPML_Package
	 */
	public static function createWpmlPackage( $data ) {
		return new \WPML_Package( $data );
	}
}
