<?php

namespace WPML\MediaTranslation;

class MediaCaptionTagsParse {
	/**
	 * @param string $text
	 *
	 * @return array
	 */
	public function get_captions( $text ) {
		$captions = [];

		if ( preg_match_all( '/\[caption (.+)\](.+)\[\/caption\]/sU', $text, $matches ) ) {

			for ( $i = 0; $i < count( $matches[0] ); $i ++ ) {
				$captions[] = new MediaCaption( $matches[0][ $i ], $matches[1][ $i ], $matches[2][ $i ] );
			}
		}

		return $captions;
	}
}