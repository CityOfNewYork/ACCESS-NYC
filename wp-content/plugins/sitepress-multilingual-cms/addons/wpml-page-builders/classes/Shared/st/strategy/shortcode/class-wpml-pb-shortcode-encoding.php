<?php

/**
 * Class WPML_PB_Register_Shortcodes
 */
class WPML_PB_Shortcode_Encoding {
	const ENCODE_TYPES_BASE64                 = 'base64';
	const ENCODE_TYPES_VISUAL_COMPOSER_LINK   = 'vc_link';
	const ENCODE_TYPES_VISUAL_COMPOSER_VALUES = 'vc_values';
	const ENCODE_TYPES_ENFOLD_LINK            = 'av_link';

	public function decode( $string, $encoding, $encoding_condition = '' ) {
		$encoded_string = $string;

		if (
			! is_string( $string ) ||
			( $encoding_condition && ! $this->should_decode( $encoding_condition ) )
		) {
			return html_entity_decode( $string );
		}

		switch ( $encoding ) {
			case self::ENCODE_TYPES_BASE64:
				/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode */
				$string = html_entity_decode( rawurldecode( base64_decode( strip_tags( $string ) ) ) );
				break;

			case self::ENCODE_TYPES_VISUAL_COMPOSER_LINK:
				$parts  = explode( '|', $string );
				$string = [];
				foreach ( $parts as $part ) {
					$data = explode( ':', $part );
					if ( count( $data ) === 2 ) {
						if ( in_array( $data[0], [ 'url', 'title' ], true ) ) {
							$string[ $data[0] ] = [
								'value'     => urldecode( $data[1] ),
								'translate' => true,
							];
						} else {
							$string[ $data[0] ] = [
								'value'     => urldecode( $data[1] ),
								'translate' => false,
							];
						}
					}
				}
				break;

			case self::ENCODE_TYPES_VISUAL_COMPOSER_VALUES:
				$string = [];
				$rows   = (array) json_decode( urldecode( $encoded_string ), true );
				foreach ( $rows as $i => $row ) {
					foreach ( $row as $key => $value ) {
						if ( 'label' === $key ) {
							$string[ $key . '_' . $i ] = [
								'value'     => $value,
								'translate' => true,
							];
						} else {
							$string[ $key . '_' . $i ] = [
								'value'     => $value,
								'translate' => false,
							];
						}
					}
				}
				break;

			case self::ENCODE_TYPES_ENFOLD_LINK:
				// Note: We can't handle 'lightbox' mode because we don't know how to re-encode it.
				$link = explode( ',', $string, 2 );
				if ( 'manually' === $link[0] ) {
					$string = $link[1];
				} elseif ( post_type_exists( $link[0] ) ) {
					$string = get_permalink( $link[1] );
				} elseif ( taxonomy_exists( $link[0] ) ) {
					$term_link = get_term_link( get_term( $link[1], $link[0] ) );
					if ( ! is_wp_error( $term_link ) ) {
						$string = $term_link;
					}
				}
				break;
		}

		return apply_filters( 'wpml_pb_shortcode_decode', $string, $encoding, $encoded_string );
	}

	public function encode( $string, $encoding ) {
		$decoded_string = $string;

		switch ( $encoding ) {
			case self::ENCODE_TYPES_BASE64:
				/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode */
				$string = base64_encode( $string );
				break;

			case self::ENCODE_TYPES_VISUAL_COMPOSER_LINK:
				$output = '';
				if ( is_array( $string ) ) {
					foreach ( $string as $key => $value ) {
						$output .= $key . ':' . rawurlencode( $value ) . '|';
					}
				}
				$string = $output;
				break;

			case self::ENCODE_TYPES_VISUAL_COMPOSER_VALUES:
				$output = [];
				foreach ( (array) $decoded_string as $combined_key => $value ) {
					$parts = explode( '_', $combined_key );
					$i     = array_pop( $parts );
					$key   = implode( '_', $parts );
					if ( ! isset( $output[ $i ] ) ) {
						$output[ $i ] = [];
					}
					$output[ $i ][ $key ] = $value;
				}
				$string = rawurlencode( wp_json_encode( $output ) );
				break;

			case self::ENCODE_TYPES_ENFOLD_LINK:
				$link = explode( ',', $string, 2 );
				if ( 'lightbox' !== $link[0] ) {
					$string = 'manually,' . $string;
				}
				break;

		}

		return apply_filters( 'wpml_pb_shortcode_encode', $string, $encoding, $decoded_string );
	}

	/**
	 * @param string $condition
	 *
	 * @return bool
	 */
	private function should_decode( $condition ) {
		preg_match( '/(?P<type>\w+):(?P<field>\w+)=(?P<value>\w+)/', $condition, $matches );

		return 'option' === $matches['type'] && get_option( $matches['field'] ) === $matches['value'];
	}
}
