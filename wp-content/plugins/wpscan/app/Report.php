<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Report.
 *
 * Generates the main report.
 *
 * @since 1.0.0
 */
class Report
{
	// Page slug.
	private $page;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @param object $parent parent.
	 * @access public
	 * @return void
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;
		$this->page   = 'wpscan';

		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Admin menu
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function menu() {
		$title = __( 'Report', 'wpscan' );

		add_submenu_page(
			'wpscan',
			$title,
			$title,
			$this->parent->WPSCAN_ROLE,
			$this->page,
			array( $this, 'page' )
		);
	}

	/**
	 * Render report page
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function page() {
		include $this->parent->plugin_dir . '/views/report.php';
	}

	/**
	 * List vulnerabilities on screen
	 *
	 * @since 1.0.0
	 *
	 * @param string $type - Type of report: WordPress, plugins, themes.
	 * @param string $name - key name of the element.
	 *
	 * @access public
	 * @return array
	 */
	public function list_api_vulnerabilities( $type, $name ) {
		$report  = $this->parent->get_report();
		$ignored = $this->parent->get_ignored_vulnerabilities();

		$null_text        = __( 'No known vulnerabilities found to affect this version', 'wpscan' );
		$not_checked_text = __( 'Not checked yet. Click the Run All button to run a scan', 'wpscan' );
		$not_found_text   = __( 'Not found in database', 'wpscan' );

		if ( empty( $report ) || ! isset( $report[ $type ] ) ) {
			return null;
		}

		$report = $report[ $type ];

		if ( array_key_exists( $name, $report ) ) {
			$report = $report[ $name ];
		} else {
			echo esc_html( $not_checked_text );
			return;
		}

		if ( isset( $report['vulnerabilities'] ) ) {
			$list = array();

			usort( $report['vulnerabilities'], array( 'self', 'sort_vulnerabilities' ) );

			foreach ( $report['vulnerabilities'] as $item ) {
				$id = 'security-checks' === $type ? $item['id'] : $item->id;

				if ( in_array( $id, $ignored, true ) ) {
					continue;
				}

				$html  = '<div class="vulnerability">';
				$html .= $this->vulnerability_severity( $item );
				$html .= '<a href="' . esc_url( 'https://wpscan.com/vulnerability/' . $item->id ) . '" target="_blank">';
				$html .= $this->parent->get_sanitized_vulnerability_title( $item );
				$html .= '</a>';
				$html .= '</div>';

				$list[] = $html;
			}

			echo empty( $list ) ? $null_text : join( '<br>', $list );

		} elseif ( $report['not_found'] ) {
			echo esc_html( $not_found_text );
		} else {
			echo esc_html( $null_text );
		}
	}

	/**
	 * Sort vulnerabilities by severity
	 *
	 * @since 1.0.0
	 * @access public
	 * @return int
	 */
	public function sort_vulnerabilities( $a, $b ) {
		$a = isset( $a->cvss->score ) ? intval( $a->cvss->score ) : 0;
		$b = isset( $b->cvss->score ) ? intval( $b->cvss->score ) : 0;

		return $b > $a ? 1 : -1;
	}

	/**
	 * Vulnerability severity
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function vulnerability_severity( $vulnerability ) {
		$plan = $this->parent->classes['account']->get_account_status()['plan'];

		if ( 'enterprise' !== $plan ) {
			return;
		}

		$html = "<div class='vulnerability-severity'>";

		if ( isset( $vulnerability->cvss->severity ) ) {
			$severity = $vulnerability->cvss->severity;
			$html    .= "<span class='wpscan-" . esc_attr( $severity ) . "'>" . esc_html( $severity ) . '</span>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Is the plugin/theme is closed
	 *
	 * @since 1.0.0
	 * @access public
	 * @return boolean
	 */
	public function is_item_closed( $type, $name ) {
		$report = $this->parent->get_report();

		if ( empty( $report ) || ! isset( $report[ $type ] ) ) {
			return null;
		}

		$report = $report[ $type ];

		if ( array_key_exists( $name, $report ) ) {
			$report = $report[ $name ];
		}

		return isset( $report['closed'] ) ? $report['closed'] : false;
	}

	/**
	 * Get all vulnerabilities
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function get_all_vulnerabilities() {
		$ret     = array();
		$report  = $this->parent->get_report();
		$ignored = $this->parent->get_ignored_vulnerabilities();

		$types = array( 'wordpress', 'plugins', 'themes', 'security-checks' );

		foreach ( $types as $type ) {
			if ( isset( $report[ $type ] ) ) {

				foreach ( $report[ $type ] as $item ) {
					if ( isset( $item['vulnerabilities'] ) ) {
						foreach ( $item['vulnerabilities'] as $vuln ) {
							$id    = 'security-checks' === $type ? $vuln['id'] : $vuln->id;
							$title = 'security-checks' === $type ? $vuln['title'] : $this->parent->get_sanitized_vulnerability_title( $vuln );

							if ( in_array( $id, $ignored, true ) ) {
								continue;
							}

							$ret[] = $title;
						}
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * Get item non-ignored vulnerabilities count
	 *
	 * @since 1.0.0
	 *
	 * @param string $type - Type of report: WordPress, plugins, themes.
	 * @param string $name - key name of the element.
	 *
	 * @access public
	 * @return int|null
	 */
	public function get_item_vulnerabilities_count( $type, $name ) {
		$report  = $this->parent->get_report();
		$ignored = $this->parent->get_ignored_vulnerabilities();

		if ( empty( $report ) ) {
			return null;
		}

		if ( isset( $report[ $type ] ) && array_key_exists( $name, $report[ $type ] ) ) {
			$report = $report[ $type ][ $name ];
		}

		foreach ( $report['vulnerabilities'] as $key => &$item ) {
			$id = 'security-checks' === $type ? $item['id'] : $item->id;

			if ( in_array( $id, $ignored, true ) ) {
				unset( $report['vulnerabilities'][ $key ] );
			}
		}

		return count( $report['vulnerabilities'] );
	}

	/**
	 * Show status icons: checked, attention and error
	 *
	 * @since 1.0.0
	 *
	 * @param string $type - Type of report: WordPress, plugins, themes.
	 * @param string $name - key name of the element.
	 *
	 * @access public
	 * @return string
	 */
	public function get_status( $type, $name ) {
		$report  = $this->parent->get_report();
		$ignored = $this->parent->get_ignored_vulnerabilities();

		if ( empty( $report ) ) {
			return null;
		}

		if ( isset( $report[ $type ] ) && array_key_exists( $name, $report[ $type ] ) ) {
			$report = $report[ $type ][ $name ];
		}

		if ( ! isset( $report['vulnerabilities'] ) ) {
			$icon = 'dashicons-no-alt is-gray';
		} elseif ( empty( $report['vulnerabilities'] ) ) {
			$icon = 'dashicons-yes is-green';
		} else {
			$count = $this->get_item_vulnerabilities_count( $type, $name );
			$icon  = 0 === $count ? 'dashicons-yes is-green' : 'dashicons-warning is-red';
		}

		return "&nbsp; <span class='dashicons $icon'></span>";
	}
}
