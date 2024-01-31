<?php

namespace WPML\MediaTranslation;

class MediaTranslationEditorLayoutFactory implements \IWPML_Backend_Action_Loader {
	public function create() {
		return new MediaTranslationEditorLayout();
	}
}