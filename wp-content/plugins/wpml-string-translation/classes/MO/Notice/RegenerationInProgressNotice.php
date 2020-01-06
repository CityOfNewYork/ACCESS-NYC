<?php

namespace WPML\ST\MO\Notice;

class RegenerationInProgressNotice extends \WPML_Notice {

	const ID    = 'mo-files-regeneration';
	const GROUP = 'mo-files';

	public function __construct() {
		$text = "WPML is updating the .mo files with the translation for strings. This will take a few more moments. During this process, translation for strings is not displaying on the front-end. You can refresh this page in a minute to see if it's done.";
		$text = __( $text, 'wpml-string-translation' );

		parent::__construct( self::ID, $text, self::GROUP );

		$this->set_dismissible( false );
		$this->set_css_classes( 'warning' );
	}

}