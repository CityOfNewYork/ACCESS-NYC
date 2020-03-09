<?php

namespace WPML\PB\Elementor\Hooks;

class DynamicElements implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	public function add_hooks() {
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'convert' ] );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert( array $data ) {
		foreach ( $data as &$item ) {
			if ( $this->isDynamicLink( $item ) ) {
					$item['settings']['__dynamic__']['link'] = $this->convertPopUpId( $item['settings']['__dynamic__']['link'] );
			}

			$item['elements'] = $this->convert( $item['elements'] );
		}

		return $data;
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	private function isDynamicLink( array $data ) {
		return isset( $data['elType'] )
		       && 'widget' === $data['elType']
		       && isset( $data['settings']['__dynamic__']['link'] );
	}

	/**
	 * @param string $tagString e.g. "[elementor-tag id="d3587f6" name="popup" settings="%7B%22popup%22%3A%228%22%7D"]"
	 *
	 * @return string
	 */
	private function convertPopUpId( $tagString ) {
		preg_match( '/name="(.*?(?="))"/', $tagString, $tagNameMatch );

		if ( ! $tagNameMatch || $tagNameMatch[1] !== 'popup' ) {
			return $tagString;
		}

		return preg_replace_callback( '/settings="(.*?(?="]))/', function( array $matches ) {
			$settings = json_decode( urldecode( $matches[1] ), true );

			if ( ! isset( $settings['popup'] ) ) {
				return $matches[0];
			}

			$settings['popup'] = $this->convertId( $settings['popup'] );
			$replace           = urlencode( json_encode( $settings ) );

			return str_replace( $matches[1], $replace, $matches[0] );

		}, $tagString );
	}

	/**
	 * @param int $elementId
	 *
	 * @return int
	 */
	private function convertId( $elementId ) {
		return apply_filters( 'wpml_object_id', $elementId, get_post_type( $elementId ), true );
	}
}
