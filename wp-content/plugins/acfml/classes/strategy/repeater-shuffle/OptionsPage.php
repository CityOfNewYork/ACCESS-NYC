<?php

namespace ACFML\Repeater\Shuffle;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\FP\Obj;
use function WPML\FP\curryN;

class OptionsPage extends Strategy {
	/**
	 * @var Collection Registered options pages IDs.
	 */
	protected $valid_ids;

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'option';
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function isValidId( $id ) {
		$starting_with_option_id = Fns::unary( Str::startsWith( Fns::__, $id ) );

		return (bool) $this->getValidOptionsPagesIds()
				->first( $starting_with_option_id );
	}

	/**
	 * Get valid options pages IDs.
	 *
	 * @return Collection Collection of registered options pages IDs.
	 *                  The default page ID being "options". However, we can still register
	 *                  an option page, with a custom page ID, with the undocumented "post_id" argument:
	 *                  ```
	 *                  acf_add_options_page([
	 *                      'page_title' => __('Portfolio options', 'acfml-option-pages-with-custom-page-id'),
	 *                      'post_id' => 'portfolio'
	 *                  ]);
	 *                  ```
	 */
	private function getValidOptionsPagesIds() {
		if ( ! isset( $this->valid_ids ) ) {
			$this->valid_ids = wpml_collect( Lst::pluck( 'post_id', acf_get_options_pages() ) );
		}

		return $this->valid_ids;
	}

	protected function getElement( $id ) {
		return null;
	}

	protected function get_element_type( $id = null ) {
		return '';
	}

	/**
	 * @param string $id
	 *
	 * @return array
	 */
	public function getAllMeta( $id ) {
		$options = [];
		$fields  = get_fields( $id );
		$fields  = $fields ? $fields : [];
		foreach ( $fields as $key => $value ) {
			$options = $this->addNormalizedValuesForFieldState( $options, '', $key, $value );
		}
		return $options;
	}

	/**
	 * @param array  $options
	 * @param string $prefix
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array
	 */
	private function addNormalizedValuesForFieldState( $options, $prefix, $key, $value ) {
		if ( $value instanceof \WP_Post || ( is_array( $value ) && isset( $value['ID'] ) ) ) {
			return array_merge( $options, [ "${prefix}${key}" => Obj::prop( 'ID', $value ) ] );
		} elseif ( $value instanceof \WP_Term ) {
			return array_merge( $options, [ "${prefix}${key}" => Obj::prop( 'term_id', $value ) ] );
		} elseif ( $this->isArrayOfStringsOrArrayOfIntegers( $value ) ) {
			return array_merge( $options, [ "${prefix}${key}" => $value ] );
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				if ( is_numeric( $index ) ) {
					foreach ( $item as $field => $field_value ) {
						$options = array_merge( $options, $this->addNormalizedValuesForFieldState( $options, "${prefix}${key}_${index}_", $field, $field_value ) );
					}
				} else {
					$options = $this->addNormalizedValuesForFieldState( $options, "${prefix}${key}_", $index, $item );
				}
			}
			return $options;
		} else {
			return array_merge( $options, [ "${prefix}${key}" => $value ] );
		}
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private function isArrayOfStringsOrArrayOfIntegers( $value ) {
		/**
		 * $intIndexTypeValue callable(callable, mixed, int|string): bool
		 */
		$intIndexTypeValue = curryN( 3, function( $typeCheck, $value, $index ) {
			return is_int( $index ) && $typeCheck( $value );
		} );

		return is_array( $value ) && (
			count( $value ) === wpml_collect( $value )->filter( $intIndexTypeValue( 'is_string' ) )->count() ||
			count( $value ) === wpml_collect( $value )->filter( $intIndexTypeValue( 'is_int' ) )->count()
		);
	}

	/**
	 * @param string $id
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function getOneMeta( $id, $key, $single = true ) {
		return get_option( $this->getOptionName( $id, $key ) );
	}

	/**
	 * @param string $id
	 * @param string $key
	 *
	 * @return void
	 */
	public function deleteOneMeta( $id, $key ) {
		delete_option( $this->getOptionName( $id, $key ) );
	}

	/**
	 * @param string $id
	 * @param string $key
	 * @param mixed  $val
	 *
	 * @return void
	 */
	public function updateOneMeta( $id, $key, $val ) {
		update_option( $this->getOptionName( $id, $key ), $val, false );
	}

	private function getOptionName( $id, $key ) {
		return "${id}_${key}";
	}

	/**
	 * Get translation ID for given element.
	 *
	 * @param string $elementId Processed option page ID.
	 *
	 * @return string The option page ID in the default language.
	 */
	public function getTrid( $elementId ) {
		$defaultLanguage = apply_filters( 'wpml_default_language', null );
		$currentLanguage = apply_filters( 'wpml_current_language', null );
		if ( $currentLanguage === $defaultLanguage ) {
			return $elementId;
		}
		return rtrim( $elementId, '_' . $currentLanguage );
	}

	/**
	 * Returns option page translations.
	 *
	 * @param string $id The option page ID.
	 *
	 * @return array
	 */
	public function getTranslations( $id ) {
		if ( ! isset( $this->element_translations[ $id ] ) ) {
			$activeLanguages = apply_filters( 'wpml_active_languages', null );
			$defaultLanguage = apply_filters( 'wpml_default_language', null );
			$currentLanguage = apply_filters( 'wpml_current_language', null );

			$getOptionName = function( $id, $languageCode ) use ( $defaultLanguage ) {
				$optionName = $this->getTrid( $id );

				if ( $languageCode !== $defaultLanguage ) {
					$optionName .= '_' . $languageCode;
				}

				return $optionName;
			};

			foreach ( $activeLanguages as $languageCode => $language ) {
				if ( $languageCode !== $currentLanguage ) {
					$this->element_translations[ $id ][ $languageCode ] = (object) [
						'element_id' => $getOptionName( $id, $languageCode ),
					];
				}
			}
		}

		return (array) Obj::prop( $id, $this->element_translations );
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function isOriginal( $id ) {
		$currentLanguages = apply_filters( 'wpml_current_language', null );
		$defaultLanguage  = apply_filters( 'wpml_default_language', null );

		return $defaultLanguage === $currentLanguages;
	}
}
