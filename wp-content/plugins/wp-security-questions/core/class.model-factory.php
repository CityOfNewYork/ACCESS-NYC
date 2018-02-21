<?php
/**
 * Model Factory Class
 * @author Flipper Code <hello@flippercode.com>
 * @package Core
 */

if ( ! class_exists( 'Flippercode_Factory_Model' ) ) {

	/**
	 * Model Factory Class
	 * @author Flipper Code <hello@flippercode.com>
	 * @version 3.0.0
	 * @package Core
	 */
	class Flippercode_Factory_Model {
		/**
		 * FactoryModel constructer.
		 */
		private $modulePrefix;
		private $modulePath;
		public function __construct($module_path, $module_prefix = '') {
			
		    $this->modulePrefix = $module_prefix;
		    $this->modulePath = $module_path;
		}
		/**
		 * Create model object by passing object type.
		 * @param  string $objectType Object Type.
		 * @return object         Return class object.
		 */
		public function create_object($objectType) {
		
			$file = $this->modulePath.$objectType.'/model.'.$objectType.'.php';
			if(file_exists($file)) {
				require_once( $file );
				$object = $this->modulePrefix.ucfirst($objectType);
				return new $object();	
			}
			

		}

	}
}
