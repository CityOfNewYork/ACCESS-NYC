<?php
/**
 * Introduce special type for controllers which render pages inside admin area
 *
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */
abstract class PMAI_Controller_Admin extends PMAI_Controller {
	/**
	 * Admin page base url (request url without all get parameters but `page`)
	 * @var string
	 */
	public $baseUrl;
	/**
	 * Parameters which is left when baseUrl is detected
	 * @var array
	 */
	public $baseUrlParamNames = array('page', 'pagenum', 'order', 'order_by', 'type', 's', 'f');
	/**
	 * Whether controller is rendered inside wordpress page
	 * @var bool
	 */
	public $isInline = false;
	/**
	 * Constructor
	 */
	public function __construct() {
		$remove = array_diff(array_keys($_GET), $this->baseUrlParamNames);
		if ($remove) {
			$this->baseUrl = remove_query_arg($remove);
		} else {
			$this->baseUrl = $_SERVER['REQUEST_URI'];
		}
		parent::__construct();							

		wp_enqueue_style('pmai-admin-style', PMAI_ROOT_URL . '/static/css/admin.css');
	
		wp_enqueue_script('pmai-script', PMAI_ROOT_URL . '/static/js/pmai.js', array('jquery'));		
		wp_enqueue_script('pmai-admin-script', PMAI_ROOT_URL . '/static/js/admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-resizable', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-nestable', 'pmxi-admin-script'));
		wp_enqueue_script('pmai-datetimepicker', PMAI_ROOT_URL . '/static/js/jquery/datetime.min.js', array('jquery'));				
		
	}	
	
	/**
	 * @see Controller::render()
	 */
	protected function render($viewPath = NULL) {
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMAI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		parent::render($viewPath);
	}
	
}