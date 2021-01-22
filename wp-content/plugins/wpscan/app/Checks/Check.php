<?php

namespace WPScan\Checks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Security Checks.
 *
 * Handles the security checks system.
 *
 * @since 1.0.0
 */
abstract class Check {
	/**
	 * A list of identified vulnerabilities for this check.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array|null
	 */
	public $vulnerabilities;

	/**
	 * Actions list.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array|null
	 */
	public $actions = array();

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id id.
	 * @param string $dir dir.
	 * @param string $parent parent.
	 *
	 * @access public
	 * @return void
	 */
	public final function __construct( $id, $dir, $parent ) {
		$this->id     = $id;
		$this->dir    = $dir;
		$this->parent = $parent;

		$count = $this->get_vulnerabilities_count();

		$this->actions[] = array(
			'id'     => 'run',
			'title'  => __( 'Run', 'wpscan' ),
			'method' => 'run',
		);

		if ( $count > 0 ) {
			$this->actions[] = array(
				'id'      => 'dismiss',
				'title'   => __( 'Dismiss', 'wpscan' ),
				'method'  => 'dismiss',
				'confirm' => true,
			);
		}

		if ( method_exists( $this, 'init' ) ) {
			$this->init();
		}
	}

	/**
	 * Check title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	abstract public function title();

	/**
	 * Check description.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	abstract public function description();

	/**
	 * Success message.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	abstract public function success_message();

	/**
	 * Add vulnerability
	 *
	 * @since 1.0.0
	 *
	 * @param string $title The vulnerability title.
	 * @param string $severity The severity, can be critical, high, medium, low and info.
	 * @param string $id Unique string to represent the vulnerability in the report object.
	 *
	 * @access public
	 * @return void
	 */
	final public function add_vulnerability( $title, $severity, $id ) {
		$vulnerability = array(
			'id'       => $id,
			'title'    => $title,
			'severity' => $severity,
		);

		$this->vulnerabilities[] = $vulnerability;
	}

	/**
	 * Get vulnerabilities.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array|null
	 */
	final public function get_vulnerabilities() {
		if ( ! empty( $this->vulnerabilities ) ) {
			return $this->vulnerabilities;
		}

		$report = $this->parent->get_report();

		if ( isset( $report['security-checks'] ) ) {
			if ( isset( $report['security-checks'][ $this->id ] ) ) {
				return $report['security-checks'][ $this->id ]['vulnerabilities'];
			}
		}

		return null;
	}

	/**
	 * Get item non-ignored vulnerabilities count
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @return int
	 */
	public function get_vulnerabilities_count() {
		$vulnerabilities = $this->get_vulnerabilities();
		$ignored         = $this->parent->get_ignored_vulnerabilities();

		if ( empty( $vulnerabilities ) ) {
			return 0;
		}

		foreach ( $vulnerabilities as $key => &$item ) {
			if ( in_array( $item['id'], $ignored, true ) ) {
				unset( $vulnerabilities[ $key ] );
			}
		}

		return count( $vulnerabilities );
	}

	/**
	 * Dismiss action
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
	public function dismiss() {
		$report  = $this->parent->get_report();
		$updated = $report;

		if ( isset( $updated['security-checks'] ) ) {
			if ( isset( $updated['security-checks'][ $this->id ] ) ) {
				$updated['security-checks'][ $this->id ]['vulnerabilities'] = array();
			}
		}

		if ( $report === $updated ) {
			return true;
		} else {
			return update_option( $this->parent->OPT_REPORT, $updated );
		}
	}

	/**
	 * Run action.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
	public function run() {
		$report  = $this->parent->get_report();
		$updated = $report;

		if ( empty( $updated ) ) {
			$updated = array(
				'security-checks' => array(),
				'plugins'         => array(),
				'themes'          => array(),
				'wordpress'       => array(),
			);
		}

		if ( isset( $updated['security-checks'][ $this->id ] ) ) {
			$updated['security-checks'][ $this->id ] = array();
		}

		$this->perform();

		if ( is_array( $this->vulnerabilities ) ) {
			$updated['security-checks'][ $this->id ]['vulnerabilities'] = $this->vulnerabilities;
		} else {
			$updated['security-checks'][ $this->id ]['vulnerabilities'] = array();
		}

		if ( $report === $updated ) {
			return true;
		} else {
			return update_option( $this->parent->OPT_REPORT, $updated );
		}
	}

	/**
	 * Perform the check and save the results.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	abstract public function perform();
}
