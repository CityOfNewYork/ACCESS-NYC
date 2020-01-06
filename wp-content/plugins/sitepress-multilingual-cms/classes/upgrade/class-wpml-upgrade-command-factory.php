<?php

class WPML_Upgrade_Command_Factory {
	/**
	 * @param string        $class_name
	 * @param array         $dependencies
	 * @param array         $scopes
	 * @param string|null   $method
	 * @param callable|null $factory_method
	 *
	 * @return WPML_Upgrade_Command_Definition
	 */
	public function create_command_definition(
		$class_name,
		array $dependencies,
		array $scopes,
		$method = null,
		callable $factory_method = null
	) {
		return new WPML_Upgrade_Command_Definition( $class_name, $dependencies, $scopes, $method, $factory_method );
	}
}
