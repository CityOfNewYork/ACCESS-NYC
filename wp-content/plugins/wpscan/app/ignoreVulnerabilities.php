<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * IgnoreVulnerabilities.
 *
 * Used for the ignore vulnerabilities logic.
 *
 * @since 1.0.0
 */
class ignoreVulnerabilities {
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
		$this->page   = 'wpscan_ignore_vulnerabilities';

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'add_meta_box_ignore_vulnerabilities' ) );
	}

	/**
	 * Ignore vulnerabilities option
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_init() {
		$total = $this->parent->get_total();

		register_setting( $this->page, $this->parent->OPT_IGNORED, array( $this, 'sanitize_ignored' ) );

		$section = $this->page . '_section';

		add_settings_section(
			$section,
			null,
			array( $this, 'introduction' ),
			$this->page
		);

		if ( $total > 0 ) {
			add_settings_field(
				$this->parent->OPT_IGNORED,
				null,
				array( $this, 'field_ignored' ),
				$this->page,
				$section
			);
		}
	}

	/**
	 * Add meta box
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function add_meta_box_ignore_vulnerabilities() {
		add_meta_box(
			'wpscan-metabox-ignore-vulnerabilities',
			__( 'Ignore Vulnerabilities', 'wpscan' ),
			array( $this, 'do_meta_box_ignore_vulnerabilities' ),
			'wpscan',
			'side',
			'low'
		);
	}

	/**
	 * Render meta box
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function do_meta_box_ignore_vulnerabilities() {
		echo '<form action="options.php" method="post">';

		settings_fields( $this->page );

		do_settings_sections( $this->page );

		submit_button();

		echo '</form>';
	}

	/**
	 * Introduction
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function introduction() { }

	/**
	 * Ignored field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function field_ignored() {
		$this->list_vulnerabilities_to_ignore( 'wordpress', get_bloginfo( 'version' ) );

		foreach ( get_plugins() as $name => $details ) {
			$this->list_vulnerabilities_to_ignore( 'plugins', $this->parent->get_plugin_slug( $name, $details ) );
		}

		foreach ( wp_get_themes() as $name => $details ) {
			$this->list_vulnerabilities_to_ignore( 'themes', $this->parent->get_theme_slug( $name, $details ) );
		}

		foreach ( $this->parent->classes['checks/system']->checks as $id => $data ) {
			$this->list_vulnerabilities_to_ignore( 'security-checks', $id );
		}
	}

	/**
	 * Sanitize ignored
	 *
	 * @since 1.0.0
	 * @param string $value value.
	 * @access public
	 * @return string
	 */
	public function sanitize_ignored( $value ) {
		if ( empty( $value ) ) {
			return array();
		}

		return $value;
	}

	/**
	 * List of vulnerabilities
	 *
	 * @since 1.0.0
	 *
	 * @param string $type - Type of report: wordpress, plugins, themes.
	 * @param string $name - key name of the element.
	 *
	 * @access public
	 * @return string
	 */
	public function list_vulnerabilities_to_ignore( $type, $name ) {
		$report = $this->parent->get_report();

		if ( isset( $report[ $type ] ) && isset( $report[ $type ][ $name ] ) ) {
			$report = $report[ $type ][ $name ];
		}

		if ( ! isset( $report['vulnerabilities'] ) ) {
			return null;
		}

		$ignored = $this->parent->get_ignored_vulnerabilities();

		foreach ( $report['vulnerabilities'] as $item ) {
			$id    = 'security-checks' === $type ? $item['id'] : $item->id;
			$title = 'security-checks' === $type ? $item['title'] : $this->parent->get_sanitized_vulnerability_title( $item );

			echo sprintf(
				'<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label><br>',
				esc_attr( $this->parent->OPT_IGNORED ),
				esc_attr( $id ),
				esc_html( in_array( $id, $ignored, true ) ? 'checked="checked"' : null ),
				wp_kses( $title, array( 'a' => array( 'href' => array() ) ) ) // Only allow a href HTML tags.
			);
		}
	}
}