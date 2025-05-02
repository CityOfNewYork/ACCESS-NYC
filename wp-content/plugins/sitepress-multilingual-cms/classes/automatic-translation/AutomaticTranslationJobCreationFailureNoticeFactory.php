<?php

namespace WPML\TM\AutomaticTranslation\Actions;

use WPML\Setup\Option;
use function WPML\Container\make;

class AutomaticTranslationJobCreationFailureNoticeFactory implements \IWPML_Backend_Action_Loader {

	public function create() {
		return make( AutomaticTranslationJobCreationFailureNotice::class );
	}
}
