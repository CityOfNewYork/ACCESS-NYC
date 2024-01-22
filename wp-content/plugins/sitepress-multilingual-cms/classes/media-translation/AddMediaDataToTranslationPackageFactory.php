<?php

namespace WPML\MediaTranslation;

class AddMediaDataToTranslationPackageFactory implements \IWPML_Backend_Action_Loader {
	public function create() {
		return new AddMediaDataToTranslationPackage( new PostWithMediaFilesFactory() );
	}
}