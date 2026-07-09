<?php

namespace ACFML\Taxonomy;

use ACFML\Helper\Taxonomy;
use ACFML\TranslationDataColumnHooks;
use ACFML\TranslationDataMetaboxHooks;
use ACFML\StringTaxonomyHooks;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		/**
		 * @var \SitePress $sitepress
		 */
		global $sitepress;

		$taxonomyHelper = new Taxonomy();
		return [
			new TranslationDataColumnHooks( $taxonomyHelper ),
			new TranslationDataMetaboxHooks( $taxonomyHelper ),
			new StringTaxonomyHooks( $sitepress ),
		];
	}
}
