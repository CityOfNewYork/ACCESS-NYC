<?php

namespace WPML\ST\TranslationJob;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\ST\Batch\Translation\StringTranslations;
use WPML\FP\Str;

use function WPML\FP\spreadArgs;

class AdminTextHooks implements \IWPML_REST_Action {

	/** @var string[]|null */
	private $optionNames = null;

	public function add_hooks() {
		Hooks::onFilter( 'wpml_tm_adjust_translation_fields' )
			->then( spreadArgs( [ $this, 'setGroupsAndLabels' ] ) );
	}

	/**
	 * @param string $slug
	 *
	 * @return bool
	 */
	private function isAdminText( $slug ) {
		if ( null === $this->optionNames ) {
			$this->optionNames = array_keys( get_option( '_icl_admin_option_names', [] ) );
		}

		return in_array( $slug, $this->optionNames, true );
	}

	/**
	 * @param list<array> $fields
	 *
	 * @return list<array>
	 */
	public function setGroupsAndLabels( $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( StringTranslations::isBatchField( $field ) ) {
				$fields[ $key ] = $this->processField( $field );
			}
		}

		return $fields;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function processField( $field ) {
		$label = Obj::prop( 'title', $field );

		if ( $this->isAdminText( $label ) ) {
			$group = Obj::prop( 'title', $field );
		} else {
			$matches = Str::match( '/^\[(.*?)\](.*)$/', $label );
			if ( ! $matches ) {
				return $field;
			}
			list( , $group, $label ) = $matches;
			if ( ! $this->isAdminText( $group ) ) {
				return $field;
			}
		}

		$groups = $this->getTopLevelGroup( $group );
		$prefix = key( $groups );

		if ( $group === $label ) {
			$label = str_replace( $prefix, '', $label );
		} else {
			$group            = str_replace( $prefix, '', $group );
			$groups[ $group ] = apply_filters( 'wpml_labelize_string', $group, 'TranslationJob' );
		}

		$field['title'] = apply_filters( 'wpml_labelize_string', $label, 'TranslationJob' );
		$field['group'] = $groups;

		return $field;
	}

	/**
	 * @param string $group
	 *
	 * @return array<string,string>
	 */
	private function getTopLevelGroup( $group ) {
		/**
		 * Allows 3rd party plugins to map their admin-text prefixes to groups in translation jobs.
		 *
		 * @param array<string,string> $prefixes
		 */
		$prefixes = apply_filters( 'wpml_st_translation_job_admin_text_prefixes_to_groups', [] );

		foreach ( $prefixes as $prefix => $topLevelGroup ) {
			if ( Str::startsWith( $prefix, $group ) ) {
				return [ $prefix => $topLevelGroup ];
			}
		}

		return [ 'admin_texts' => 'Admin Texts' ];
	}

}
