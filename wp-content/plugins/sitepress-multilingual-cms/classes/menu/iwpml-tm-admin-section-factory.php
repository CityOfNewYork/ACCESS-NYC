<?php

interface IWPML_TM_Admin_Section_Factory {

	/**
	 * Returns an instance of a class implementing \IWPML_TM_Admin_Section.
	 *
	 * @return \IWPML_TM_Admin_Section
	 */
	public function create();
}
