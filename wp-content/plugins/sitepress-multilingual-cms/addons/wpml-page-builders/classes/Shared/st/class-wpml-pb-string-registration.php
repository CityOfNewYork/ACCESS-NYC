<?php

/**
 * Class WPML_PB_String_Registration
 */
class WPML_PB_String_Registration {

	/** @var IWPML_PB_Strategy $strategy */
	private $strategy;
	/** @var WPML_ST_String_Factory $string_factory */
	private $string_factory;
	/** @var  WPML_ST_Package_Factory $package_factory */
	private $package_factory;
	/** @var WPML_Translate_Link_Targets $translate_link_targets */
	private $translate_link_targets;

	/** @var callable $set_link_translations */
	private $set_link_translations;

	/** @var  bool $migration_mode */
	private $migration_mode;

	/**
	 * WPML_PB_String_Registration constructor.
	 *
	 * @param IWPML_PB_Strategy           $strategy
	 * @param WPML_ST_String_Factory      $string_factory
	 * @param WPML_ST_Package_Factory     $package_factory
	 * @param WPML_Translate_Link_Targets $translate_link_targets
	 * @param callable                    $set_link_translations
	 * @param bool                        $migration_mode
	 */
	public function __construct(
		IWPML_PB_Strategy $strategy,
		WPML_ST_String_Factory $string_factory,
		WPML_ST_Package_Factory $package_factory,
		WPML_Translate_Link_Targets $translate_link_targets,
		callable $set_link_translations,
		$migration_mode = false
	) {
		$this->strategy               = $strategy;
		$this->string_factory         = $string_factory;
		$this->package_factory        = $package_factory;
		$this->translate_link_targets = $translate_link_targets;
		$this->set_link_translations  = $set_link_translations;
		$this->migration_mode         = $migration_mode;
	}

	/**
	 * @param int    $post_id
	 * @param string $content
	 * @param string $name
	 *
	 * @return null|int
	 */
	public function get_string_id_from_package( $post_id, $content, $name = '' ) {
		$package_data = $this->strategy->get_package_key( $post_id );
		$package      = $this->package_factory->create( $package_data );
		$string_name  = $name ? $name : md5( $content );
		$string_name  = $package->sanitize_string_name( $string_name );
		$string_value = $content;

		return apply_filters( 'wpml_string_id_from_package', null, $package, $string_name, $string_value );
	}

	public function get_string_title( $string_id ) {
		return apply_filters( 'wpml_string_title_from_id', null, $string_id );
	}

	/**
	 * Register string.
	 *
	 * @param int          $post_id  Post Id.
	 * @param string|mixed $content  String content.
	 * @param string       $type     String editor type.
	 * @param string       $title    String title.
	 * @param string       $name     String name.
	 * @param int          $location String location.
	 * @param string       $wrap_tag String wrap tag.
	 *
	 * @return null|integer $string_id
	 */
	public function register_string(
		$post_id,
		$content = '',
		$type = 'LINE',
		$title = '',
		$name = '',
		$location = 0,
		$wrap_tag = ''
	) {

		$string_id = 0;

		if ( is_string( $content ) && trim( $content ) ) {

			$string_name = $name ? $name : md5( $content );

			if ( $this->migration_mode ) {

				$string_id = $this->get_string_id_from_package( $post_id, $content, $string_name );
				$this->update_string_data( $string_id, $location, $wrap_tag );

			} else {

				if ( 'LINK' === $type && ! $this->translate_link_targets->is_internal_url( $content ) ) {
					$type = 'LINE';
				}

				$string_value = $content;
				$package      = $this->strategy->get_package_key( $post_id );
				$string_title = $title ? $title : $string_value;
				do_action( 'wpml_register_string', $string_value, $string_name, $package, $string_title, $type );

				$string_id = $this->get_string_id_from_package( $post_id, $content, $string_name );
				$this->update_string_data( $string_id, $location, $wrap_tag );

				if ( 'LINK' === $type ) {
					call_user_func( $this->set_link_translations, $string_id );
				}
			}
		}

		return $string_id;
	}

	/**
	 * Update string data: location and wrap tag.
	 * Wrap tag is used for SEO significance, can contain values as h1 ... h6, etc.
	 *
	 * @param int    $string_id String id.
	 * @param string $location  String location inside of the page builder content.
	 * @param string $wrap_tag  String wrap tag for SEO significance.
	 */
	private function update_string_data( $string_id, $location, $wrap_tag ) {
		$string = $this->string_factory->find_by_id( $string_id );
		$string->set_location( $location );
		$string->set_wrap_tag( $wrap_tag );
	}
}
