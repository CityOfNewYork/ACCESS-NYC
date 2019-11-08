<?php

class WPML_Compatibility_Disqus_Factory implements IWPML_Frontend_Action_Loader {
	/**
	 * @return WPML_Compatibility_Disqus
	 */
	public function create() {
		global $sitepress;

		return new WPML_Compatibility_Disqus( $sitepress );
	}

}