<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SiteHealth.
 *
 * Displays vulnerabilities in WordPress site health page.
 *
 * @since 1.0.0
 */
class SiteHealth {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_filter( 'site_status_tests', array( $this, 'add_site_health_tests' ) );
	}

	/**
	 * Add site-health page tests.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function add_site_health_tests( $tests ) {
		$tests['direct']['wpscan_check'] = array(
			'label' => __( 'WPScan Vulnerabilities Check' ),
			'test'  => array( $this, 'site_health_tests' ),
		);

		return $tests;
	}

	/**
	 * Do site-health page tests
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function site_health_tests() {
		$report = $this->parent->get_report();
		$total  = $this->parent->get_total_not_ignored();
		$vulns  = $this->parent->classes['report']->get_all_vulnerabilities();

		/**
		 * Default, no vulnerabilities found
		 */
		$result = array(
			'label'       => __( 'No known vulnerabilities found', 'wpscan' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Security', 'wpscan' ),
				'color' => 'gray',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Vulnerabilities can be exploited by hackers and cause harm to your website.', 'wpscan' )
			),
			'actions'     => '',
			'test'        => 'wpscan_check',
		);

		/**
		 * If vulnerabilities found.
		 */
		if ( ! empty($report) && $total > 0 ) {
			$result['status']      = 'critical';
			$result['label']       = sprintf( _n( 'Your site is affected by %d security vulnerability', 'Your site is affected by %d security vulnerabilities', $total, 'wpscan' ), $total );
			$result['description'] = 'WPScan detected the following security vulnerabilities in your site:';

			foreach ( $vulns as $vuln ) {
				$result['description'] .= '<p>';
				$result['description'] .= "<span class='dashicons dashicons-warning' style='color: crimson;'></span> &nbsp";
				$result['description'] .= wp_kses( $vuln, array( 'a' => array( 'href' => array() ) ) ); // Only allow a href HTML tags.
				$result['description'] .= '</p>';
			}
		}

		return $result;
	}
}
