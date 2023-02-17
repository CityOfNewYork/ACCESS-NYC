<?php

namespace WPML\CLI\Core\Commands;

interface IWPML_Command_Factory {

	/**
	 * @return ICommand
	 */
	public function create();
}