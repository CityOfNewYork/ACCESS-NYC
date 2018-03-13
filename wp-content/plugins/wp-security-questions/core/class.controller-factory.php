<?php
/**
 * Controller Factory Class
 * @author Flipper Code <hello@flippercode.com>
 * @package Core
 */

if ( ! class_exists( 'Flippercode_Factory_Controller' ) ) {

	/**
	 * Controller Factory Class
	 * @author Flipper Code <hello@flippercode.com>
	 * @version 3.0.0
	 * @package Core
	 */
	class Flippercode_Factory_Controller {
		/**
		 * FactoryController constructer.
		 */

		private $modulePrefix;
		private $modulePath;
		private $mainControllerClass;

		public function __construct($module_path, $module_prefix = '') {
			
		    $this->modulePrefix = $module_prefix;
		    $this->modulePath = $module_path;
		    $this->mainControllerClass = plugin_dir_path( __FILE__ ).'class.controller.php';
		}

		/**
		 * Create controller object by passing object type.
		 * @param  string $objectType Object Type.
		 * @return object         Return class object.
		 */
		public function create_object($objectType) {
			
			if ( file_exists( $this->mainControllerClass ) ) {
				require_once( $this->mainControllerClass );
				return $coreControllerObj = new Flippercode_Core_Controller( $objectType,$this->modulePath,$this->modulePrefix);
			}

		}

	}
}
