<?php

namespace WPML\TM\TranslationDashboard\EncodedFieldsValidation;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Post;
use WPML\TM\TranslationDashboard\SentContentMessages;
use function WPML\FP\invoke;
use function WPML\FP\spreadArgs;

class Validator {
	/** @var \WPML_Encoding_Validation */
	private $encoding_validation;
	/** @var \WPML_Element_Translation_Package */
	private $package_helper;
	/** @var SentContentMessages */
	private $sentContentMessages;
	/** @var FieldTitle */
	private $fieldTitle;
	/** @var \WPML_PB_Factory */
	private $pbFactory;

	public function __construct(
		\WPML_Encoding_Validation $encoding_validation,
		\WPML_Element_Translation_Package $package_helper,
		SentContentMessages $sentContentMessages,
		FieldTitle $fieldTitle,
		\WPML_PB_Factory $pbFactory
	) {
		$this->encoding_validation = $encoding_validation;
		$this->package_helper      = $package_helper;
		$this->sentContentMessages = $sentContentMessages;
		$this->fieldTitle          = $fieldTitle;
		$this->pbFactory           = $pbFactory;
	}

	/**
	 * $data may contain two keys: 'post' and 'package'. Each of them has the same shape:
	 * [
	 *   idOfElement1 => [
	 *      checked: 1,
	 *      type: 'post',
	 *   ],
	 *   idOfElement2 => [
	 *      type: 'post',
	 *   ],
	 * ] and so on.
	 *
	 * If element has "checked" field, it means it has been selected for translation.
	 * Therefore, if we want to filter it out from translation, we have to remove that field.
	 *
	 * The "validateTMDashboardInput" performs similar check for both "post" and "package" lists,
	 * checks if their elements contains encoded fields and removes them from the list.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function validateTMDashboardInput( $data ) {
		$postInvalidElements = [];
		if ( isset( $data['post'] ) ) {
			$postInvalidElements = $this->findPostsWithEncodedFields( $this->getCheckedIds( 'post', $data ) );
			$data                = $this->excludeInvalidElements( 'post', $data, Lst::pluck( 'elementId', $postInvalidElements ) );
		}

		$packageInvalidElements = [];
		if ( isset( $data['package'] ) ) {
			$packageInvalidElements = $this->findPackagesWithEncodedFields( $this->getCheckedIds( 'package', $data ) );
			$data                   = $this->excludeInvalidElements( 'package', $data, Lst::pluck( 'elementId', $packageInvalidElements ) );
		}

		$invalidElements = array_merge( $postInvalidElements, $packageInvalidElements );
		if ( count( $invalidElements ) ) {
			// Display the error message only if there are invalid elements.
			$this->sentContentMessages->postsWithEncodedFieldsHasBeenSkipped( $invalidElements );
		}

		return $data;
	}

	/**
	 * Get list of ids of selected posts/packages
	 *
	 * @param 'post'|'package' $type
	 * @param array $data
	 *
	 * @return []
	 */
	private function getCheckedIds( $type, $data ) {
		return \wpml_collect( Obj::propOr( [], $type, $data ) )
			->filter( Obj::prop( 'checked' ) )
			->keys()
			->toArray();
	}

	/**
	 * It removes "checked" property from the elements that have "encoded" fields. It means they will not be sent to translation.
	 *
	 * @param 'post'|'package' $type
	 * @param array $data
	 * @param int[] $ids
	 *
	 * @return array
	 */
	private function excludeInvalidElements( $type, $data, $invalidElementIds ) {
		return (array) Obj::over( Obj::lensProp( $type ), function ( $elements ) use ( $invalidElementIds ) {
			return \wpml_collect( $elements )
				->map( function ( $element, $elementId ) use ( $invalidElementIds ) {
					if ( Lst::includes( $elementId, $invalidElementIds ) ) {
						return Obj::removeProp( 'checked', $element );
					}

					return $element;
				} )
				->toArray();
		}, $data );
	}

	/**
	 * @param int[] $postIds
	 *
	 * @return ErrorEntry[]
	 */
	private function findPostsWithEncodedFields( $postIds ) {
		$appendPackage = function ( \WP_Post $post ) {
			$package = $this->package_helper->create_translation_package( $post->ID, true );
			return [ $post, $package ];
		};

		$isFieldEncoded = function ( $field ) {
			$decodedFieldData = base64_decode( $field['data'] );

			return array_key_exists( 'format', $field )
			             && 'base64' === $field['format']
			             && $this->encoding_validation->is_base64_with_100_chars_or_more( $decodedFieldData );

			/**
			 * @todo : we should handle not to block the whole job from being translated but instead we should exclude the problematic field from the job and send it to be translated without that field.
			 * @todo check Youtrack ticket
			 * @see : https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1694
			 */
		};

		$getInvalidFieldData = function ( $field, $slug ) {
			return [
				'title'   => $this->fieldTitle->get( $slug ),
				'content' => base64_decode( $field['data'] ),
			];
		};

		$tryToGetError = function ( \WP_Post $post, $package ) use ( $isFieldEncoded, $getInvalidFieldData ) {
			$invalidFields = \wpml_collect( $package['contents'] )
				->filter( $isFieldEncoded )
				->map( $getInvalidFieldData )
				->values()
				->toArray();

			if ( $invalidFields ) {
				return new ErrorEntry( $post->ID, $package['title'], $invalidFields );
			}

			return null;
		};

		return \wpml_collect( $postIds )
			->map( Post::get() )
			->filter()
			->map( $appendPackage )
			->map( spreadArgs( $tryToGetError ) )
			->filter()
			->toArray();

	}

	/**
	 * @param int[] $packageIds
	 *
	 * @return ErrorEntry[]
	 */
	private function findPackagesWithEncodedFields( $packageIds ) {
		$getInvalidFieldData = function ( $field, $slug ) {
			return [
				'title'   => $this->fieldTitle->get( $slug ),
				'content' => $field,
			];
		};

		/**
		 * @param \WPML_Package $package
		 *
		 * @return ErrorEntry|null
		 */
		$tryToGetError = function ( $package ) use ( $getInvalidFieldData ) {
			$invalidFields = \wpml_collect( Obj::propOr( [], 'string_data', $package ) )
				->filter( [ $this->encoding_validation, 'is_base64_with_100_chars_or_more' ] )
				->map( $getInvalidFieldData )
				->values()
				->toArray();

			if ( $invalidFields ) {
				return new ErrorEntry( $package->ID, $package->title, $invalidFields );
			}

			return null;
		};

		return \wpml_collect( $packageIds )
			->map( function ( $packageId ) {
				return $this->pbFactory->get_wpml_package( $packageId );
			} )
			->filter( Obj::prop( 'ID' ) )
			->map( $tryToGetError )
			->filter()
			->toArray();

	}
}
