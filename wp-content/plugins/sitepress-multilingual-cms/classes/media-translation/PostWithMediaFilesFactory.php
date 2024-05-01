<?php

namespace WPML\MediaTranslation;

class PostWithMediaFilesFactory {
	public function create( $post_id ) {
		global $sitepress, $iclTranslationManagement;

		return new PostWithMediaFiles(
			$post_id,
			new MediaImgParse(),
			new MediaAttachmentByUrlFactory(),
			$sitepress,
			new \WPML_Custom_Field_Setting_Factory( $iclTranslationManagement )
		);
	}
}