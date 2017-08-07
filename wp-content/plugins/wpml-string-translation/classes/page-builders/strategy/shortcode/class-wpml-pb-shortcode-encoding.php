<?php

/**
 * Class WPML_PB_Register_Shortcodes
 */
class WPML_PB_Shortcode_Encoding {

	public function decode( $string, $encoding ) {
		switch ( $encoding ) {
			case 'base64':
				$string = rawurldecode( base64_decode( strip_tags( $string ) ) );
				break;

			case 'vc_link':
				$parts  = explode( '|', $string );
				$string = array();
				foreach ( $parts as $part ) {
					$data = explode( ':', $part );
					if ( sizeof( $data ) == 2 ) {
						if ( in_array( $data[0], array( 'url', 'title' ) ) ) {
							$string[ $data[0] ] = array( 'value' => urldecode( $data[1] ), 'translate' => true );
						} else {
							$string[ $data[0] ] = array( 'value' => urldecode( $data[1] ), 'translate' => false );
						}
					}
				}
				break;

			case 'av_link':
				// Note: We can't handle 'lightbox' mode because we don't know how to re-encode it
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

		return $string;
	}

	public function encode( $string, $encoding ) {
		switch ( $encoding ) {
			case 'base64':
				$string = base64_encode( $string );
				break;

			case 'vc_link':
				$output = '';
				foreach ( $string as $key => $value ) {
					$output .= $key . ':' . rawurlencode( $value ) . '|';
				}
				$string = $output;
				break;

			case 'av_link':
				$link = explode( ',', $string, 2 );
				if ( $link[0] != 'lightbox' ) {
					$string = 'manually,' . $string;
				}
				break;

		}


		return $string;
	}


}
