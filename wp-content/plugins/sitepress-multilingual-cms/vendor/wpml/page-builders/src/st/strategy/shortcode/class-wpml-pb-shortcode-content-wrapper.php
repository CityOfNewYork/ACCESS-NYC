<?php

use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Str;
use function WPML\FP\pipe;

class WPML_PB_Shortcode_Content_Wrapper {

	const WRAPPER_SHORTCODE_NAME  = 'wpml_string_wrapper';
	const GUTENBERG_OPENING_START = '<!-- wp:';

	/** @var string $content */
	private $content;

	/** @var array $valid_shortcodes */
	private $valid_shortcodes;

	/** @var array $shortcodes */
	private $shortcodes = array();

	/** @var array $content_array */
	private $content_array;

	/** @var array $insert_wrapper */
	private $insert_wrapper = array();

	/**
	 * @param string $content
	 * @param array  $valid_shortcodes
	 */
	public function __construct( $content, array $valid_shortcodes ) {
		$this->content          = $content;
		$this->valid_shortcodes = $valid_shortcodes;
	}

	public function get_wrapped_content() {
		$this->split_content();
		$this->parse_shortcodes();
		$this->analyze_unwrapped_text();
		$this->insert_wrappers();
		return $this->content;
	}

	/**
	 * This is a multibyte safe version of `str_split`
	 */
	private function split_content() {
		$length = mb_strlen( $this->content );

		for ( $i = 0; $i < $length; $i++ ) {
			$this->content_array[] = mb_substr( $this->content, $i, 1 );
		}
	}

	private function parse_shortcodes() {
		$close_bracket_position = false;
		$content_length         = count( $this->content_array );

		for ( $i = 0; $i < $content_length; $i++ ) {
			if ( false !== $close_bracket_position && $close_bracket_position >= $i ) {
				continue;
			}

			if ( '[' === $this->content_array[ $i ] ) {
				$close_bracket_position = $this->parse_shortcode( $i );
			}
		}
	}

	/**
	 * @param int $open_bracket_position
	 *
	 * @return int
	 */
	private function parse_shortcode( $open_bracket_position ) {
		$shortcode_name         = $this->get_shortcode_name( $open_bracket_position );
		$close_bracket_position = $this->get_shortcode_end( $open_bracket_position, $shortcode_name );
		$is_closing             = isset( $this->content_array[ $open_bracket_position + 1 ] )
		                          && '/' === $this->content_array[ $open_bracket_position + 1 ];

		if ( ! in_array( $shortcode_name, $this->valid_shortcodes, true ) ) {
			return $close_bracket_position;
		}

		if ( $is_closing ) {
			$shortcode_index = $this->find_last_opened_shortcode( $shortcode_name );

			if ( null !== $shortcode_index ) {
				$this->shortcodes[ $shortcode_index ]['end'] = $close_bracket_position;
				$this->remove_nested_shortcodes_between(
					$this->shortcodes[ $shortcode_index ]['start'],
					$close_bracket_position
				);
			}
		} else {
			$this->shortcodes[] = array(
				'name'  => $shortcode_name,
				'start' => $open_bracket_position,
				'end'   => $close_bracket_position,
			);
		}

		return $close_bracket_position;
	}

	/**
	 * @param int $start
	 * @param int $end
	 */
	private function remove_nested_shortcodes_between( $start, $end ) {
		foreach ( $this->shortcodes as $key => $shortcode ) {

			if ( $start < $shortcode['start'] && $end > $shortcode['end'] ) {
				unset( $this->shortcodes[ $key ] );
			}
		}
	}

	private function analyze_unwrapped_text() {
		$next_unwrapped_text_start = 0;

		foreach ( $this->shortcodes as $shortcode ) {
			$unwrapped_text_start      = $next_unwrapped_text_start;
			$unwrapped_text_end        = $shortcode['start'] - 1;
			$next_unwrapped_text_start = $shortcode['end'] + 1;

			if ( $unwrapped_text_start < $unwrapped_text_end ) {
				$this->set_wrapper_positions( $unwrapped_text_start, $unwrapped_text_end );
			}
		}

		$max_content_char_position = mb_strlen( $this->content ) - 1;

		// For unwrapped text closing the content.
		if ( $next_unwrapped_text_start < $max_content_char_position ) {
			$this->set_wrapper_positions( $next_unwrapped_text_start, $max_content_char_position );
		}
	}

	/**
	 * @param int $start
	 * @param int $end
	 */
	private function set_wrapper_positions( $start, $end ) {
		$raw_chunk = mb_substr( $this->content, $start, $end - $start );

		if ( '' === trim( $raw_chunk ) ) {
			// the chunk is an empty string, we don't need to wrap it.
			return;
		}

		$chunk_start        = $this->get_wrapper_insert_position( $start, 'open' );
		$unwrapped_text_end = $this->get_wrapper_insert_position( $end, 'close' );

		$this->insert_wrapper[ $chunk_start ]        = '[' . self::WRAPPER_SHORTCODE_NAME . ']';
		$this->insert_wrapper[ $unwrapped_text_end ] = '[/' . self::WRAPPER_SHORTCODE_NAME . ']';
	}

	/**
	 * @param int    $position
	 * @param string $type
	 *
	 * @return null|int
	 */
	private function get_wrapper_insert_position( $position, $type ) {
		if ( 'close' === $type ) {
			$increment = - 1;
		} else {
			$increment = 1;
		}

		while ( isset( $this->content_array[ $position ] )
				&& in_array( $this->content_array[ $position ], array( "\n", "\r" ), true )
		) {
			$position = $position + $increment;
		}

		if ( 'close' === $type ) {
			$position++;
		}

		return $position;
	}

	/**
	 * @param int $open_bracket_position
	 *
	 * @return string
	 */
	private function get_shortcode_name( $open_bracket_position ) {
		$char_position = $open_bracket_position + 1;
		$name          = '';

		while ( isset( $this->content_array[ $char_position ] )
				&& ( '' === $name || ! in_array( $this->content_array[ $char_position ], array( ' ', ']' ), true ) )
		) {
			if ( '/' !== $this->content_array[ $char_position ] ) {
				$name .= $this->content_array[ $char_position ];
			}

			$char_position++;
		}

		return $name;
	}

	/**
	 * @param int    $open_bracket_position
	 * @param string $shortcode_name
	 *
	 * @return int
	 */
	private function get_shortcode_end( $open_bracket_position, $shortcode_name ) {
		$char_position = $open_bracket_position + mb_strlen( $shortcode_name );
		$is_in_quotes  = false;

		while ( isset( $this->content_array[ $char_position ] )
				&& ( ']' !== $this->content_array[ $char_position ] || $is_in_quotes )
		) {
			if ( in_array( $this->content_array[ $char_position ], array( '"', "'" ), true ) ) {
				$is_in_quotes = ! $is_in_quotes;
			}

			$char_position++;
		}

		return $char_position;
	}

	/**
	 * @param string $shortcode_name
	 *
	 * @return int|null
	 */
	private function find_last_opened_shortcode( $shortcode_name ) {
		$last_matching_index = null;

		foreach ( $this->shortcodes as $shortcode_index => $shortcode ) {
			if ( $shortcode['name'] === $shortcode_name ) {
				$last_matching_index = (int) $shortcode_index;
			}
		}

		return $last_matching_index;
	}

	private function insert_wrappers() {
		$offset = 0;

		foreach ( $this->insert_wrapper as $wrapper_position => $wrapper ) {
			$insert_position = $wrapper_position + $offset;
			$before          = mb_substr( $this->content, 0, $insert_position );
			$after           = mb_substr( $this->content, $insert_position );
			$this->content   = $before . $wrapper . $after;
			$offset          = $offset + mb_strlen( $wrapper );
		}
	}

	/**
	 * @param string $content
	 * @param array  $shortcodes
	 *
	 * @return string
	 */
	public static function maybeWrap( $content, array $shortcodes ) {
		$notGutenbergContent  = pipe( Str::includes( self::GUTENBERG_OPENING_START ), Logic::not() );
		$containsOneShortcode = pipe( Str::match( '/' . get_shortcode_regex( $shortcodes ) . '/s' ), Logic::isEmpty(), Logic::not() );

		return Maybe::of( $content )
			->filter( $notGutenbergContent )
			->filter( $containsOneShortcode )
			->filter( [ self::class, 'isStrippedContentDifferent' ] )
			->map( [ self::class, 'wrap' ] )
			->getOrElse( $content );
	}

	/**
	 * This will flag some regular text not wrapped in a shortcode.
	 * e.g. "[foo] Some text not wrapped [bar]"
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	public static function isStrippedContentDifferent( $content ) {
		$content_with_stripped_shortcode = preg_replace( '/\[([\S]*)[^\]]*\][\s\S]*\[\/(\1)\]|\[[^\]]*\]/', '', $content );
		$content_with_stripped_shortcode = trim( $content_with_stripped_shortcode );
		return ! empty( $content_with_stripped_shortcode ) && trim( $content ) !== $content_with_stripped_shortcode;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public static function wrap( $content ) {
		return '[' . self::WRAPPER_SHORTCODE_NAME . ']' . $content . '[/' . self::WRAPPER_SHORTCODE_NAME . ']';
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public static function unwrap( $content ) {
		return str_replace(
			[
				'[' . self::WRAPPER_SHORTCODE_NAME . ']',
				'[/' . self::WRAPPER_SHORTCODE_NAME . ']'
			],
			'',
			$content
		);
	}
}
