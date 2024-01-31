<?php

namespace WPML\MediaTranslation;

class MediaAttachmentByUrlFactory {
	public function create( $url, $language ) {
		global $wpdb;

		return new MediaAttachmentByUrl( $wpdb, $url, $language );
	}
}