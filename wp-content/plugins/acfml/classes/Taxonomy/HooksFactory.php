<?php

namespace ACFML\Taxonomy;

use ACFML\Helper\Taxonomy;
use ACFML\TranslationDataColumnHooks;
use ACFML\TranslationDataMetaboxHooks;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		$taxonomyHelper = new Taxonomy();
		return [
			new TranslationDataColumnHooks( $taxonomyHelper ),
			new TranslationDataMetaboxHooks( $taxonomyHelper ),
		];
	}
}
