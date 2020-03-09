<?php

use WPML\Upgrade\CommandsStatus;

class WPML_Upgrade {
	const SCOPE_ADMIN     = 'admin';
	const SCOPE_AJAX      = 'ajax';
	const SCOPE_FRONT_END = 'front-end';

	/** @var array */
	private $commands;
	const UPDATE_STATUSES_KEY = 'wpml_update_statuses';

	/** @var SitePress */
	private $sitepress;

	/** @var WPML_Upgrade_Command_Factory */
	private $command_factory;

	/** @var CommandsStatus */
	private $command_status;

	/** @var bool $upgrade_in_progress */
	private $upgrade_in_progress;

	/**
	 * WPML_Upgrade constructor.
	 *
	 * @param array                        $commands
	 * @param SitePress                    $sitepress
	 * @param WPML_Upgrade_Command_Factory $command_factory
	 * @param CommandsStatus               $command_status
	 */
	public function __construct(
		array $commands,
		SitePress $sitepress,
		WPML_Upgrade_Command_Factory $command_factory,
		CommandsStatus $command_status = null
	) {
		$this->add_commands( $commands );
		$this->sitepress       = $sitepress;
		$this->command_factory = $command_factory;
		$this->command_status  = $command_status ?: new CommandsStatus();
	}

	/**
	 * @param array $commands
	 */
	public function add_commands( array $commands ) {
		foreach ( $commands as $command ) {
			if ( $command instanceof WPML_Upgrade_Command_Definition ) {
				$this->commands[] = $command;
			}
		}
	}

	public function run() {
		$result = false;

		/**
		 * Add commands to the upgrade logic.
		 *
		 * The filter must be added before the `wpml_loaded` action is fired (the action is fired on `plugins_loaded`).
		 *
		 * @param array $commands An empty array.
		 * @param array $new_commands Array of classes created with \wpml_create_upgrade_command_definition.
		 *
		 * @since 4.1.0
		 * @see   \wpml_create_upgrade_command_definition
		 *
		 */
		$new_commands = apply_filters( 'wpml_upgrade_commands', array() );
		if ( $new_commands && is_array( $new_commands ) ) {
			$this->add_commands( $new_commands );
		}

		if ( $this->sitepress->get_wp_api()->is_admin() ) {
			if ( $this->sitepress->get_wp_api()->is_ajax() ) {
				$result = $this->run_ajax();
			} else {
				$result = $this->run_admin();
			}
		} elseif ( $this->sitepress->get_wp_api()->is_front_end() ) {
			$result = $this->run_front_end();
		}

		$this->set_upgrade_completed();

		return $result;
	}

	private function get_commands_by_scope( $scope ) {
		$results = array();
		/** @var WPML_Upgrade_Command_Definition $command */
		foreach ( $this->commands as $command ) {
			if ( in_array( $scope, $command->get_scopes(), true ) ) {
				$results[] = $command;
			}
		}

		return $results;
	}

	private function get_admin_commands() {
		return $this->get_commands_by_scope( self::SCOPE_ADMIN );
	}

	private function get_ajax_commands() {
		return $this->get_commands_by_scope( self::SCOPE_AJAX );
	}

	private function get_front_end_commands() {
		return $this->get_commands_by_scope( self::SCOPE_FRONT_END );
	}

	private function run_admin() {
		return $this->run_commands( $this->get_admin_commands(), 'maybe_run_admin' );
	}

	private function run_ajax() {
		return $this->run_commands( $this->get_ajax_commands(), 'maybe_run_ajax' );
	}

	private function run_front_end() {
		return $this->run_commands( $this->get_front_end_commands(), 'maybe_run_front_end' );
	}

	private function run_commands( $commands, $default ) {
		$results = array();
		/** @var WPML_Upgrade_Command_Definition $command */
		foreach ( $commands as $command ) {
			$results[] = $this->run_command( $command, $default );
		}

		return $results;
	}

	private function run_command( WPML_Upgrade_Command_Definition $command_definition, $default ) {
		$method = $default;
		if ( $command_definition->get_method() ) {
			$method = $command_definition->get_method();
		}

		if ( ! $this->command_status->hasBeenExecuted( $command_definition->get_class_name() ) ) {
			$this->set_upgrade_in_progress();
			$upgrade = $command_definition->create();

			return $this->$method( $upgrade );
		}

		return null;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_admin( IWPML_Upgrade_Command $upgrade ) {
		if ( $upgrade->run_admin() ) {
			$this->mark_command_as_executed( $upgrade );
		}

		return $upgrade->get_results();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_front_end( IWPML_Upgrade_Command $upgrade ) {
		if ( $upgrade->run_frontend() ) {
			$this->mark_command_as_executed( $upgrade );
		}

		return $upgrade->get_results();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_ajax( IWPML_Upgrade_Command $upgrade ) {
		if ( $this->nonce_ok( $upgrade ) && $upgrade->run_ajax() ) {
			$this->mark_command_as_executed( $upgrade );
			$this->sitepress->get_wp_api()->wp_send_json_success( '' );
		}

		return $upgrade->get_results();
	}

	private function nonce_ok( $class ) {
		$ok = false;

		$class_name = $this->get_command_id( get_class( $class ) );
		if ( isset( $_POST['action'] ) && $_POST['action'] === $class_name ) {
			$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( $this->sitepress->get_wp_api()->wp_verify_nonce( $nonce, $class_name . '-nonce' ) ) {
				$ok = true;
			}
		}

		return $ok;
	}

	/**
	 * @param IWPML_Upgrade_Command $class
	 */
	public function mark_command_as_executed( IWPML_Upgrade_Command $class ) {
		$this->command_status->markAsExecuted( get_class( $class ) );
	}

	/**
	 * @param string $class_name
	 *
	 * @return string
	 */
	private function get_command_id( $class_name ) {
		return str_replace( '_', '-', strtolower( $class_name ) );
	}

	private function set_upgrade_in_progress() {
		if ( ! $this->upgrade_in_progress ) {
			$this->upgrade_in_progress = true;
			set_transient( WPML_Upgrade_Loader::TRANSIENT_UPGRADE_IN_PROGRESS, true, MINUTE_IN_SECONDS );
		}
	}

	private function set_upgrade_completed() {
		if ( $this->upgrade_in_progress ) {
			$this->upgrade_in_progress = false;
			delete_transient( WPML_Upgrade_Loader::TRANSIENT_UPGRADE_IN_PROGRESS );
		}
	}
}

