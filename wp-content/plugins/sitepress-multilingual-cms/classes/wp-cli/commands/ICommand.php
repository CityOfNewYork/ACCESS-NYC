<?php
namespace WPML\CLI\Core\Commands;

interface ICommand {

	/**
	 * @param string[]             $args
	 * @param array<string,string> $assoc_args
	 *
	 * @return mixed
	 */
	public function __invoke( $args, $assoc_args );

	/**
	 * @return string
	 */
	public function get_command();
}