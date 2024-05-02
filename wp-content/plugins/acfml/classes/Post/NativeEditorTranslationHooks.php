<?php

namespace ACFML\Post;

use ACFML\Helper\FieldGroup;
use ACFML\Helper\Resources;
use WPML\API\Sanitize;
use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\FP\Wrapper;
use WPML\LIB\WP\Hooks;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class NativeEditorTranslationHooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		$postId           = isset( $_GET['post'] ) ? (int) $_GET['post'] : null;
		$trid             = isset( $_GET['trid'] ) ? (int) $_GET['trid'] : null;
		$postType         = Sanitize::stringProp( 'post_type', $_GET );
		$isNewTranslation = (bool) $trid;

		if ( $trid ) {
			Hooks::onFilter( 'acf/load_value', PHP_INT_MAX, 3 )
				->then( spreadArgs( Fns::withoutRecursion( Fns::identity(), self::preFillPostTranslationField( $trid ) ) ) );
		}

		// $isNotFieldGroup :: ( string|null, int|null ) -> bool
		$isNotFieldGroup = function( $postType, $postId ) {
			$postType = $postType ?: get_post_type( $postId );
			return FieldGroup::CPT !== $postType;
		};

		// $isPostTranslation :: void -> bool
		$isPostTranslation = function() use ( $postId ) {
			if ( ! $postId ) {
				return false;
			}

			$originalId = (int) PostTranslations::getOriginalId( $postId );

			return $originalId && $postId !== $originalId;
		};

		if (
			( $isNewTranslation || $isPostTranslation() )
			&& $isNotFieldGroup( $postType, $postId )
		) {
			self::loadFieldLockFilters();

			Hooks::onAction( 'admin_enqueue_scripts' )
				->then( [ self::class, 'enqueueAssets' ] );
		}
	}

	/**
	 * @return void
	 */
	public static function loadFieldLockFilters() {
		Hooks::onFilter( 'acf/field_wrapper_attributes', 10, 2 )
			->then( spreadArgs( [ self::class, 'addClassToFieldWrapper' ] ) );

		Hooks::onFilter( 'acf/get_field_label', 10, 2 )
			->then( spreadArgs( [ self::class, 'addClassToFieldLabel' ] ) );
	}

	/**
	 * @param int $trid
	 *
	 * @return \Closure
	 */
	private static function preFillPostTranslationField( $trid ) {
		// $getOriginalId :: void -> int
		$getOriginalId = Fns::memorize( function() use ( $trid ) {
			return (int) \SitePress::get_original_element_id_by_trid( $trid );
		} );

		/**
		 * @param mixed  $value  The value to preview.
		 * @param string $postId The post ID for this value.
		 * @param array  $field  The field array.
		 *
		 * @return mixed
		 */
		return function( $value, $postId, $field ) use ( $getOriginalId ) {
			// $isNullOrFalse :: mixed -> bool
			$isNullOrFalseOrEmpty = Lst::includes( Fns::__, [ null, false, '' ] );

			// $isCopiable :: array -> bool
			$isCopiable = pipe(
				Obj::prop( 'wpml_cf_preferences' ),
				Lst::includes( Fns::__, [ WPML_COPY_ONCE_CUSTOM_FIELD, WPML_COPY_CUSTOM_FIELD ] )
			);

			// $isAutoDraft :: int|string -> bool
			$isAutoDraft = pipe( 'get_post_status', Relation::equals( 'auto-draft' ) );

			if (
				$isNullOrFalseOrEmpty( $value )
				&& $isCopiable( $field )
				&& $isAutoDraft( $postId )
			) {
				$originalId = $getOriginalId();

				if ( $originalId !== $postId ) {
					return acf_get_value( $originalId, $field );
				}
			}

			return $value;
		};
	}

	/**
	 * @param array $wrapper
	 * @param array $field
	 *
	 * @return array
	 */
	public static function addClassToFieldWrapper( $wrapper, $field ) {
		if ( self::isCopied( $field ) ) {
			return Obj::over( Obj::lensProp( 'class' ), Str::concat( Fns::__, ' acfml-field-copied' ), $wrapper );
		}

		return $wrapper;
	}

	/**
	 * @param string $labelHtml
	 * @param array  $field
	 *
	 * @return string
	 */
	public static function addClassToFieldLabel( $labelHtml, $field ) {
		if ( self::isCopied( $field ) ) {
			return '<span class="acfml-field-label-copied">' . $labelHtml . '</span>';
		}

		return $labelHtml;
	}

	/**
	 * @param array $field
	 *
	 * @return bool
	 */
	private static function isCopied( $field ) {
		return Relation::propEq( 'wpml_cf_preferences', WPML_COPY_CUSTOM_FIELD, $field );
	}

	/**
	 * @return void
	 */
	public static function enqueueAssets() {
		Wrapper::of( [
			'name' => 'acfmlNativeEditorTranslationEdit',
			'data' => [
				'strings' => [
					'tooltip' => esc_html__( 'This field value is copied from the default language and will be kept in sync across languages.', 'acfml' ),
				],
			],
		] )->map( Resources::enqueueApp( 'native-editor-translation-edit' ) );
	}
}
