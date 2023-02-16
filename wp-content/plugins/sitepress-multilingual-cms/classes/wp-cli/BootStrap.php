<?php

namespace WPML\CLI\Core;

use WPML\CLI\Core\Commands\ClearCacheFactory;
use WPML\CLI\Core\Commands\ICommand;

class BootStrap {
	const MAIN_COMMAND = 'wpml';

	/**
	 * @throws \Exception The exception thrown by \WP_CLI::add_command.
	 */
	public function init() {
		$commands_factory = [
			ClearCacheFactory::class,
		];

		foreach ( $commands_factory as $command_factory ) {
			$command_factory_obj = new $command_factory();

			$command = $command_factory_obj->create();

			$this->add_command( $this->getFullCommand( $command ), $command );
		}
	}

	/**
	 * @param string   $command_text The subcommand.
	 * @param callable $command      Command implementation as a class, function or closure.
	 *
	 * @throws \Exception The exception thrown by \WP_CLI::add_command.
	 */
	private function add_command( $command_text, $command ) {
		\WP_CLI::add_command( $command_text, $command );
	}

	/**
	 * @param ICommand $command Command implementation as a class, function or closure.
	 *
	 * @return string The sub command prefixed by the top-level command (all trimmed).
	 */
	private function getFullCommand( $command ) {
		return trim( self::MAIN_COMMAND . ' ' . $command->get_command() );
	}
}
