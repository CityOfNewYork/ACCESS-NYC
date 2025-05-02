<?php

use WPML\PB\TranslationJob\Groups;

class WPML_Gutenberg_Strings_Registration {

	/** @var WPML\PB\Gutenberg\StringsInBlock\StringsInBlock $strings_in_blocks */
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

	/** @var WPML_Translate_Link_Targets $translate_link_targets */
	private $translate_link_targets;

	/** @var callable $set_link_translations */
	private $set_link_translations;

	/** @var int */
	private $sequence = 0;

	public function __construct(
		WPML\PB\Gutenberg\StringsInBlock\StringsInBlock $strings_in_blocks,
		WPML_ST_String_Factory $string_factory,
		WPML_PB_Reuse_Translations $reuse_translations,
		WPML_PB_String_Translation $string_translation,
		WPML_Translate_Link_Targets $translate_link_targets,
		callable $set_link_translations
	) {
		$this->strings_in_blocks      = $strings_in_blocks;
		$this->string_factory         = $string_factory;
		$this->reuse_translations     = $reuse_translations;
		$this->string_translation     = $string_translation;
		$this->translate_link_targets = $translate_link_targets;
		$this->set_link_translations  = $set_link_translations;
	}

	/**
	 * @param WP_Post $post
	 * @param array   $package_data
	 */
	public function register_strings( WP_Post $post, $package_data ) {
		do_action( 'wpml_start_string_package_registration', $package_data );
		/* phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase */
		do_action( 'wpml_start_GB_register_strings', $post, $package_data );

		$original_strings       = $this->string_translation->get_package_strings( $package_data );
		$this->leftover_strings = $original_strings;
		$this->string_location  = 1;

		$this->register_blocks(
			WPML_Gutenberg_Integration::parse_blocks( $post->post_content ),
			$package_data,
			$post->ID
		);

		$current_strings = $this->string_translation->get_package_strings( $package_data );

		$this->reuse_translations->find_and_reuse_translations( $original_strings, $current_strings, $this->leftover_strings );

		/* phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase */
		do_action( 'wpml_end_GB_register_strings', $post, $package_data );
		do_action( 'wpml_delete_unused_package_strings', $package_data );
	}

	/**
	 * @param array $blocks
	 * @param array $package_data
	 */
	public function register_strings_from_widget( array $blocks, array $package_data ) {
		do_action( 'wpml_start_string_package_registration', $package_data );

		$original_strings       = $this->string_translation->get_package_strings( $package_data );
		$this->leftover_strings = $original_strings;
		$this->string_location  = 1;

		foreach ( $blocks as $blockId => $block ) {
			$block['id'] = $blockId;
			$this->register_blocks( [ $block ], $package_data, null, [ 'block-' . $blockId ] );
		}

		$current_strings = $this->string_translation->get_package_strings( $package_data );

		$this->reuse_translations->find_and_reuse_translations( $original_strings, $current_strings, $this->leftover_strings );

		do_action( 'wpml_delete_unused_package_strings', $package_data );
	}

	/**
	 * @param array $blocks
	 * @param array $package_data
	 * @param int   $post_id
	 * @param array $crumbs
	 */
	private function register_blocks( array $blocks, array $package_data, $post_id, $crumbs = [] ) {

		foreach ( $blocks as $block ) {
			$block   = WPML_Gutenberg_Integration::sanitize_block( $block );
			$strings = $this->strings_in_blocks->find( $block );

			/**
			 * Replace the sequence with the image id if we want a thumbnail preview in ATE.
			 *
			 * @param int   $sequence
			 * @param mixed $block
			 */
			$sequence  = apply_filters( 'wpml_pb_replace_sequence_with_attachment_id', $this->sequence, $block );
			$group     = $this->getGroupIdOfBlock( $block, $sequence );
			$newCrumbs = $crumbs;
			if ( ! $this->isLayoutBlock( $block ) ) {
				$newCrumbs[] = $group;
			}

			$this->sequence ++;

			if ( empty( $strings ) ) {
				if ( $post_id ) {
					apply_filters( 'wpml_pb_register_strings_in_content', false, $post_id, $block->innerHTML );
				}
			} else {
				foreach ( $strings as $string ) {

					if ( $post_id && apply_filters( 'wpml_pb_register_strings_in_content', false, $post_id, $string->value ) ) {
						continue;
					}

					if ( 'LINK' === $string->type && ! $this->translate_link_targets->is_internal_url( $string->value ) ) {
						$string->type = 'LINE';
					}

					$groupLabel = Groups::buildGroupLabel( $newCrumbs, $string->name );

					do_action(
						'wpml_register_string',
						$string->value,
						$string->id,
						$package_data,
						$groupLabel,
						$string->type
					);

					$this->update_string_location( $package_data, $string );

					if ( 'core/heading' === $block->blockName ) {
						$wrap_tag = (string) isset( $block->attrs['level'] ) ? $block->attrs['level'] : 2;
						$wrap_tag = 'h' . $wrap_tag;
						$this->update_wrap_tag( $package_data, $string, $wrap_tag );
					}

					if ( 'LINK' === $string->type ) {
						$string_id = apply_filters( 'wpml_string_id_from_package', 0, $package_data, $string->id, $string->value );
						call_user_func( $this->set_link_translations, $string_id );
					}

					$this->remove_string_from_leftovers( $string->value );
				}
			}

			if ( ! empty( $block->innerBlocks ) ) {
				$this->register_blocks( $block->innerBlocks, $package_data, $post_id, $newCrumbs );
			}
		}
	}

	private function isLayoutBlock( WP_Block_Parser_Block $block ) : bool {
		return isset( $block->attrs['layout'] );
	}

	private function getGroupIdOfBlock( WP_Block_Parser_Block $block, int $sequence ) : string {
		return str_replace( '/', '-', $block->blockName ) . '-' . $sequence;
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

	/**
	 * @param string $string_value
	 */
	private function remove_string_from_leftovers( $string_value ) {
		$string_hash = $this->string_translation->get_string_hash( $string_value );

		if ( isset( $this->leftover_strings[ $string_hash ] ) ) {
			unset( $this->leftover_strings[ $string_hash ] );
		}
	}
}
