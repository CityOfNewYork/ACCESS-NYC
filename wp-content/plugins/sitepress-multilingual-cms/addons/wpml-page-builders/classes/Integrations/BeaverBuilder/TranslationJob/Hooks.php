<?php

namespace WPML\PB\BeaverBuilder\TranslationJob;

use WPML_Beaver_Builder_Data_Settings;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var WPML_Beaver_Builder_Data_Settings $dataSettings */
	private $dataSettings;

	public function __construct( WPML_Beaver_Builder_Data_Settings $dataSettings ) {
		$this->dataSettings = $dataSettings;
	}

	public function add_hooks() {
		add_filter( 'wpml_tm_translation_job_data', [ $this, 'filterFieldsByPageBuilderKind' ], PHP_INT_MAX, 2 );
	}

	/**
	 * @param array              $translationPackage
	 * @param \stdClass|\WP_Post $post
	 *
	 * @return array
	 */
	public function filterFieldsByPageBuilderKind( array $translationPackage, $post ) {
		if ( ! $this->isPostPackage( $translationPackage, $post ) ) {
			return $translationPackage;
		}

		if ( $this->dataSettings->is_handling_post( $post->ID ) ) {
			return $this->removeFieldsFromKind( $translationPackage, $post->ID, 'gutenberg' );
		}

		/**
		 *
		 */
		return $this->removeFieldsFromKind( $translationPackage, $post->ID, 'beaver-builder' );
	}

	/**
	 * @param array              $translationPackage
	 * @param \stdClass|\WP_Post $post
	 *
	 * @return bool
	 */
	private function isPostPackage( array $translationPackage, $post ) {
		return 'external' !== $translationPackage['type'] && isset( $post->ID );
	}

	/**
	 * @param array  $translationPackage
	 * @param int    $postId
	 * @param string $kindSlug
	 *
	 * @return array
	 */
	private function removeFieldsFromKind( array $translationPackage, $postId, $kindSlug ) {
		$packageIdToRemove = wpml_collect( apply_filters( 'wpml_st_get_post_string_packages', [], $postId ) )
			->pluck( 'ID', 'kind_slug' )
			->get( $kindSlug );

		if ( $packageIdToRemove ) {
			$isFieldFromPackageToRemove = function( $value, $key ) use ( $packageIdToRemove ) {
				return preg_match( '/^package-string-' . $packageIdToRemove . '-/', $key );
			};

			$translationPackage['contents'] = wpml_collect( $translationPackage['contents'] )
				->reject( $isFieldFromPackageToRemove )
				->toArray();
		}

		return $translationPackage;
	}
}
