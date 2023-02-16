<?php

class OTGS_Installer_Autoloader {

	private $classMap = [];

	public function initialize() {
		include_once dirname( __FILE__ ) . '/functions-core.php';
		include_once dirname( __FILE__ ) . '/functions-templates.php';
		include_once dirname( __FILE__ ) . '/utilities/FP/functions.php';

		spl_autoload_register( [ $this, 'autoload' ] );
	}

	public function autoload( $class_name ) {
		if ( array_key_exists( $class_name, $this->getClassMap() ) ) {
			$file = $this->classMap[ $class_name ];
			include $file;
		}
	}

	private function getClassMap() {
		if ( ! $this->classMap ) {
			$this->classMap = require dirname( __FILE__ ) . '/otgs-installer-autoload-classmap.php';
		}

		return $this->classMap;
	}
}
