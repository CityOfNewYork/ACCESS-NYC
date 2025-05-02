<?php

/**
 * A helper class for wp_kses_post to avoid self-closing tag formatting issues.
 *
 * The wp_kses_post function internally calls wp_kses_attr,
 * which reformats self-closing HTML tags by adding a space before the slash.
 * For example, <br/> becomes <br />, which can break string translations
 * for strings containing such tags.
 *
 * This helper class stores all self-closing tags and their positions
 * in an array. After calling wp_kses_post, it restores the tags
 * to their original format.
 */
class WPML_Kses_Post {
	public static function wp_kses_post_preserve_tags_format( $input ) {
		$original_tags  = self::get_string_tags( $input );
		$filtered_input = wp_kses_post( $input );
		$filtered_tags  = self::get_string_tags( $filtered_input );

		if ( $filtered_input === $input || empty( $filtered_tags ) ) {
			return $filtered_input;
		}

		$offset = 0;
		$original_tag_pos = 0;
		$filtered_tag_pos = 0;

		foreach ( $original_tags as $tag ) {
			if ( empty( $filtered_tags[ $filtered_tag_pos ] ) ) {
				break;
			}

			if ( $original_tags[ $original_tag_pos ][ 'tag_name' ] !== $filtered_tags[ $filtered_tag_pos ][ 'tag_name' ] ) {
				$original_tag_pos++;
				continue;
			}

			$original_tag_length = strlen( $tag[ 'tag' ] );
			$filtered_tag_length = strlen( $filtered_tags[ $filtered_tag_pos ][ 'tag' ] );
			$filtered_input      = substr_replace( $filtered_input, $tag[ 'tag' ], $filtered_tags[ $filtered_tag_pos ][ 'position' ] - $offset, $filtered_tag_length );
			$offset              += $filtered_tag_length - $original_tag_length;
			$original_tag_pos++;
			$filtered_tag_pos++;
		}

		return $filtered_input;
	}

	public static function get_string_tags( $string ) {
		$tags = [ ];
		preg_match_all( '/<[^>]+>/', $string, $matches, PREG_OFFSET_CAPTURE );

		foreach ( $matches[ 0 ] as $match ) {
			$tags[ ] = [
				'tag'      => $match[ 0 ],
				'tag_name' => strtolower( trim( str_replace( [ '<', '>', '/' ], '', $match[ 0 ] ) ) ),
				'position' => $match[ 1 ],
			];
		}

		return $tags;
	}
}
