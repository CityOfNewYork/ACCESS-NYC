<?php

namespace ACFML\Strings;

class TranslationJobHooks implements \IWPML_Action {

	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @param Factory $factory
	 */
	public function __construct( Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		if ( self::isEnabled() ) {
			add_filter( 'wpml_translation_package_by_language', [ $this, 'addStringsToTranslationPackage' ], 10, 3 );
			add_action( 'wpml_translation_job_saved', [ $this, 'saveFieldGroupStringsTranslations' ], 10, 3 );
		}
	}

	/**
	 * @return bool
	 */
	public static function isEnabled() {
		return ! ( defined( 'ACFML_EXCLUDE_FIELD_GROUP_STRINGS_IN_POST_JOBS' ) && ACFML_EXCLUDE_FIELD_GROUP_STRINGS_IN_POST_JOBS );
	}

	/**
	 * @param int       $translatedPostId
	 * @param array     $fields
	 * @param \stdClass $job
	 *
	 * @return void
	 */
	public function saveFieldGroupStringsTranslations( $translatedPostId, $fields, $job ) {
		$this->factory->createTranslationJobFilter()->saveTranslations( $fields, $job );
	}

	/**
	 * @param array          $package
	 * @param \WP_Post|mixed $post
	 * @param string         $targetLang
	 *
	 * @return array
	 */
	public function addStringsToTranslationPackage( $package, $post, $targetLang ) {
		if ( $post instanceof \WP_Post ) {
			return $this->factory->createTranslationJobFilter()->appendStrings( $package, $post, $targetLang ); // phpcs:ignore
		}

		return $package;
	}

}
