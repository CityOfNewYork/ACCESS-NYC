<?php
/**
 * @author OnTheGo Systems
 */
interface IWPML_Action_Loader_Factory {
	/**
	 * @return IWPML_Action|IWPML_Action[]|callable|null
	 */
	public function create();
}
