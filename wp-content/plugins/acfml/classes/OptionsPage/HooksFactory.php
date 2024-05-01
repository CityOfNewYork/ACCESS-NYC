<?php

namespace ACFML\OptionsPage;

use ACFML\Helper\OptionsPage;
use ACFML\TranslationDataColumnHooks;
use ACFML\TranslationDataMetaboxHooks;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		$helper = new OptionsPage();
		return [
			new TranslationDataColumnHooks( $helper ),
			new TranslationDataMetaboxHooks( $helper ),
		];
	}
}
