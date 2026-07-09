<?php

namespace ACFML\Strings;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Relation;

class FieldHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

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

	/**
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'acf/update_field_group', [ $this, 'registerGroupAndFieldsAndLayouts' ] );

		add_filter( 'acf/load_field_group', Fns::withoutRecursion( Fns::identity(), [ $this, 'translateGroup' ] ) );
		add_filter( 'acf/load_field', [ $this, 'translateField' ] );

		add_action( 'acf/delete_field_group', [ $this, 'deleteFieldGroupPackage' ] );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function registerGroupAndFieldsAndLayouts( $fieldGroup ) {
		$this->translator->registerGroupAndFieldsAndLayouts( $fieldGroup );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return array
	 */
	public function translateGroup( $fieldGroup ) {
		if ( self::isAcfFieldGroupScreen() ) {
			return $fieldGroup;
		}

		/**
		 * Filters whether an ACF entity should be translated.
		 *
		 * @param bool   $shouldTranslate Whether the entity should be translated. Default true.
		 * @param array  $fieldGroup      The ACF entity array.
		 * @param string $entityKind      The context in which the filter is applied. Default 'group'.
		 *
		 * @return bool Whether the field group should be translated.
		 */
		$shouldTranslate = apply_filters( 'acfml_should_translate_acf_entity', true, $fieldGroup, 'group' );

		if ( ! $shouldTranslate ) {
			return $fieldGroup;
		}

		return $this->translator->translateGroup( $fieldGroup );
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public function translateField( $field ) {
		if ( self::isAcfFieldGroupScreen() ) {
			return $field;
		}

		if ( self::shouldSkipField( $field ) ) {
			return $field;
		}

		$shouldTranslate = apply_filters( 'acfml_should_translate_acf_entity', true, $field, 'field' );
		if ( ! $shouldTranslate ) {
			return $field;
		}

		return $this->translator->translateField( $field );
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function deleteFieldGroupPackage( $fieldGroup ) {
		$this->factory->createPackage( $fieldGroup['ID'], Package::FIELD_GROUP_PACKAGE_KIND_SLUG )->delete();
	}

	/**
	 * @return bool
	 */
	private static function isAcfFieldGroupScreen() {
		return ! function_exists( 'acf_is_screen' ) || acf_is_screen( 'acf-field-group' );
	}

	/**
	 * @param array $field
	 *
	 * @return bool
	 */
	private static function shouldSkipField( array $field ) {
		// $isSeamlessClone :: ( array ) -> bool
		$isSeamlessClone = Logic::allPass( [
			Relation::propEq( 'type', 'clone' ),
			Relation::propEq( 'display', 'seamless' ),
		] );

		return $isSeamlessClone( $field );
	}
}
