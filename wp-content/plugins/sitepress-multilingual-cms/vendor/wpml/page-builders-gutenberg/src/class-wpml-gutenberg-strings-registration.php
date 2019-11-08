<?php

class WPML_Gutenberg_Strings_Registration {

	/** @var WPML_Gutenberg_Strings_In_Block $strings_in_blocks */
	private $strings_in_blocks;

	/** @var WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/** @var WPML_PB_Reuse_Translations $reuse_translations */
	private $reuse_translations;

	/** @var WPML_PB_String_Translation $string_translation */
	private $string_translation;

	/** @var int $string_location */
	private $string_location;

	/** @var array $leftover_strings */
	private $leftover_strings;

	public function __construct(
		WPML_Gutenberg_Strings_In_Block $strings_in_blocks,
		WPML_ST_String_Factory $string_factory,
		WPML_PB_Reuse_Translations $reuse_translations,
		WPML_PB_String_Translation $string_translation
	) {
		$this->strings_in_blocks = $strings_in_blocks;
		$this->string_factory = $string_factory;
		$this->reuse_translations = $reuse_translations;
		$this->string_translation = $string_translation;
	}

	/**
	 * @param WP_Post $post
	 * @param array $package_data
	 */
	public function register_strings( WP_Post $post, $package_data ) {
		do_action( 'wpml_start_string_package_registration', $package_data );

		$this->leftover_strings = $original_strings = $this->string_translation->get_package_strings( $package_data );
		$this->string_location  = 1;

		$this->register_blocks(
			WPML_Gutenberg_Integration::parse_blocks( $post->post_content ),
			$package_data
		);

		$current_strings = $this->string_translation->get_package_strings( $package_data );

		$this->reuse_translations->find_and_reuse_translations( $original_strings, $current_strings, $this->leftover_strings );

		do_action( 'wpml_delete_unused_package_strings', $package_data );
	}

	/**
	 * @param array $blocks
	 * @param array $package_data
	 */
	private function register_blocks( array $blocks, array $package_data ) {

		foreach ( $blocks as $block ) {

			$block   = WPML_Gutenberg_Integration::sanitize_block( $block );
			$strings = $this->strings_in_blocks->find( $block );

			foreach ( $strings as $string ) {

				do_action(
					'wpml_register_string',
					$string->value,
					$string->id,
					$package_data,
					$string->name,
					$string->type
				);

				$this->update_string_location( $package_data, $string );

				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( 'core/heading' === $block->blockName ) {
					// phpcs:enable
					$wrap_tag = (string) isset( $block->attrs['level'] ) ? $block->attrs['level'] : 2;
					$wrap_tag = 'h' . $wrap_tag;
					$this->update_wrap_tag( $package_data, $string, $wrap_tag );
				}

				$this->remove_string_from_leftovers( $string->value );
			}

			if ( isset( $block->innerBlocks ) ) {
				$this->register_blocks( $block->innerBlocks, $package_data );
			}
		}
	}

	private function update_string_location( array $package_data, stdClass $string_data ) {
		$string_id = apply_filters( 'wpml_string_id_from_package', 0, $package_data, $string_data->id, $string_data->value );
		$string    = $this->string_factory->find_by_id( $string_id );

		if ( $string_id ) {
			$string->set_location( $this->string_location );
			$this->string_location++;
		}
	}

	/**
	 * Update string wrap tag.
	 * Used for SEO, can contain (h1...h6, etc.)
	 *
	 * @param array    $package_data Package.
	 * @param stdClass $string_data  String in the package.
	 * @param string   $wrap_tag     String wrap.
	 */
	private function update_wrap_tag( $package_data, stdClass $string_data, $wrap_tag ) {
		$string_id = apply_filters( 'wpml_string_id_from_package', 0, $package_data, $string_data->id, $string_data->value );
		$string    = $this->string_factory->find_by_id( $string_id );

		if ( $string_id ) {
			$string->set_wrap_tag( $wrap_tag );
		}
	}

	/** @var string $string_value */
	private function remove_string_from_leftovers( $string_value ) {
		$string_hash = $this->string_translation->get_string_hash( $string_value );

		if ( isset( $this->leftover_strings[ $string_hash ] ) ) {
			unset( $this->leftover_strings[ $string_hash ] );
		}
	}
}
