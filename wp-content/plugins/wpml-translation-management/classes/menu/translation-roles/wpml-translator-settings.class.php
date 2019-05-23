<?php

class WPML_Translator_Settings extends WPML_TM_Translators_View implements WPML_Translator_Settings_Interface {

	public function get_twig_template() {
		return 'translators.twig';
	}

	public function get_template_paths() {
		return array ();
	}

}