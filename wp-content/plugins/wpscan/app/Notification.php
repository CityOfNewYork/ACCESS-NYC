<?php

namespace WPScan;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notification.
 *
 * Used for the Notifications logic.
 *
 * @since 1.0.0
 */
class Notification {
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
	public function __construct($parent) {
		$this->parent = $parent;
		$this->page   = 'wpscan_notification';

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'add_meta_box_notification' ) );
	}

	/**
	 * Notification Options
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_init() {
		$total = $this->parent->get_total();

		register_setting( $this->page, $this->parent->OPT_EMAIL, array( $this, 'sanitize_email' ) );
		register_setting( $this->page, $this->parent->OPT_INTERVAL, array( $this, 'sanitize_interval' ) );

		$section = $this->page . '_section';

		add_settings_section(
			$section,
			null,
			array( $this, 'introduction' ),
			$this->page
		);

		add_settings_field(
			$this->parent->OPT_EMAIL,
			__( 'E-mail', 'wpscan' ),
			array( $this, 'field_email' ),
			$this->page,
			$section
		);

		add_settings_field(
			$this->parent->OPT_INTERVAL,
			__( 'Send Alerts', 'wpscan' ),
			array( $this, 'field_interval' ),
			$this->page,
			$section
		);
	}

	/**
	 * Add meta box
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function add_meta_box_notification() {
		add_meta_box(
			'wpscan-metabox-notification',
			__( 'Notification', 'wpscan' ),
			array( $this, 'do_meta_box_notification' ),
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
	public function do_meta_box_notification() {
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
	 * @return string
	 */
	public function introduction() {
		echo '<p>' . __( 'Fill in the options below if you want to be notified by mail about new vulnerabilities. To add multiple e-mail addresses comma separate them.', 'wpscan' ) . '</p>';
	}

	/**
	 * Email field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_email()
	{
		echo sprintf(
			'<input type="text" name="%s" value="%s" class="regular-text" placeholder="email@domain.com, copy@domain.com">',
			esc_attr( $this->parent->OPT_EMAIL ),
			esc_attr( get_option( $this->parent->OPT_EMAIL, '' ) )
		);
	}

	/**
	 * Interval field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function field_interval() {
		$interval = get_option( $this->parent->OPT_INTERVAL, 'd' );

		echo '<select name="' . $this->parent->OPT_INTERVAL . '">';
		echo '<option value="o" ' . selected( 'o', $interval, false ) . '>' . __( 'Disabled', 'wpscan' ) . '</option>';
		echo '<option value="d" ' . selected( 'd', $interval, false ) . '>' . __( 'Daily', 'wpscan' ) . '</option>';
		echo '<option value="1" ' . selected( 1, $interval, false ) . '>' . __( 'Every Monday', 'wpscan' ) . '</option>';
		echo '<option value="2" ' . selected( 2, $interval, false ) . '>' . __( 'Every Tuesday', 'wpscan' ) . '</option>';
		echo '<option value="3" ' . selected( 3, $interval, false ) . '>' . __( 'Every Wednesday', 'wpscan' ) . '</option>';
		echo '<option value="4" ' . selected( 4, $interval, false ) . '>' . __( 'Every Thursday', 'wpscan' ) . '</option>';
		echo '<option value="5" ' . selected( 5, $interval, false ) . '>' . __( 'Every Friday', 'wpscan' ) . '</option>';
		echo '<option value="6" ' . selected( 6, $interval, false ) . '>' . __( 'Every Saturday', 'wpscan' ) . '</option>';
		echo '<option value="7" ' . selected( 7, $interval, false ) . '>' . __( 'Every Sunday', 'wpscan' ) . '</option>';
		echo '<option value="m" ' . selected( 'm', $interval, false ) . '>' . __( 'Every Month', 'wpscan' ) . '</option>';
		echo '</selected>';
	}

	/**
	 * Sanitize email
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function sanitize_email( $value ) {
		if ( ! empty( $value ) ) {
			$emails = explode( ',', $value );

			foreach ( $emails as $email ) {
				if ( ! is_email( trim( $email ) ) ) {
					add_settings_error( $this->parent->OPT_EMAIL, 'invalid-email', __( 'You have entered an invalid e-mail address.', 'wpscan' ) );
					$value = '';
				}
			}
		}

		return $value;
	}

	/**
	 * Sanitize interval
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function sanitize_interval( $value ) {
		$allowed_values = array( 'o', 'd', 1, 2, 3, 4, 5, 6, 7, 'm' );

		if ( ! in_array( $value, $allowed_values ) ) {
			// return default value.
			return 'd';
		}

		return $value;
	}

	/**
	 * Send the notification
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function notify() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$email    = get_option( $this->parent->OPT_EMAIL );
		$interval = get_option( $this->parent->OPT_INTERVAL, 'd' );

		// Check email or if notifications are disabled.
		if ( empty( $email ) || 'o' === $interval ) {
			return;
		}

		// Check weekly interval.
		if ( is_numeric( $interval ) && date( 'N' ) !== $interval ) {
			return;
		}

		// Check monthly interval.
		if ( $interval === 'm' && date( 'j' ) !== 1 ) {
			return;
		}

		// Send email.
		$has_vulnerabilities = false;

		$msg  = '<!doctype html><html><head><meta charset="utf-8"></head><body>';
		$msg .= '<p>' . __( 'Hello,', 'wpscan' ) . '</p>';
		$msg .= '<p>' . sprintf(__( 'Some vulnerabilities were found in %s, visit the site for more details.', 'wpscan' ), '<a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'name' ) . '</a>' ) . '</p>';

		// WordPress
		$list = $this->email_vulnerabilities( 'wordpress' , get_bloginfo( 'version' ));

		if ( ! empty( $list ) ) {
			$has_vulnerabilities = true;

			$msg .= '<p><b>WordPress</b><br>';
			$msg .= join( '<br>', $list ) . '</p>';
		}

		// Plugins.
		foreach ( get_plugins() as $name => $details ) {
			$slug = $this->parent->get_plugin_slug( $name, $details );
			$list = $this->email_vulnerabilities( 'plugins', $slug );

			if ( ! empty( $list ) ) {
				$has_vulnerabilities = true;

				$msg .= '<p><b>' . __( 'Plugin', 'wpscan' ) . ' ' . esc_html( $details['Name'] ) . '</b><br>';
				$msg .= join( '<br>', $list ) . '</p>';
			}
		}

		// Themes.
		foreach ( wp_get_themes() as $name => $details ) {
			$slug = $this->parent->get_theme_slug( $name, $details );
			$list = $this->email_vulnerabilities( 'themes', $slug );

			if ( ! empty( $list ) ) {
				$has_vulnerabilities = true;

				$msg .= '<p><b>' . __( 'Theme', 'wpscan' ) . ' ' . esc_html( $details['Name'] ) . '</b><br>';
				$msg .= join( '<br>', $list ) . '</p>';
			}
		}

		// Security checks.
		foreach ( $this->parent->classes['checks/system']->checks as $id => $data ) {
			$list = $this->email_vulnerabilities( 'security-checks', $id );

			if ( ! empty( $list ) ) {
				$has_vulnerabilities = true;

				$msg .= '<p><b>' . __( 'Security check', 'wpscan' ) . ' ' . esc_html( $data['instance']->title() ) . '</b><br>';
				$msg .= join( '<br>', $list ) . '</p>';
			}
		}

		$msg .= '</body></html>';

		if ( $has_vulnerabilities ) {
			$subject = sprintf(
				__( 'Some vulnerabilities were found in %s', 'wpscan' ),
				get_bloginfo( 'name' )
			);

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			wp_mail( $email, $subject, $msg, $headers );
		}
	}

	/**
	 * List of vulnerabilities to send by mail
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function email_vulnerabilities( $type, $name ) {
		$report  = $this->parent->get_report()[ $type ];
		$ignored = $this->parent->get_ignored_vulnerabilities();

		if ( array_key_exists( $name, $report ) ) {
			$report = $report[ $name ];
		}

		if ( ! isset( $report['vulnerabilities'] ) ) {
			return null;
		}

		$list = [];

		foreach ( $report['vulnerabilities'] as $item ) {
			$id    = 'security-checks' === $type ? $item['id'] : $item->id;
			$title = 'security-checks' === $type ? $item['title'] : $this->parent->get_sanitized_vulnerability_title( $item );

			if ( in_array( $id, $ignored ) ) {
				continue;
			}

			if ( 'security-checks' !== $type ) {
				$html  = '<a href="' . esc_url( 'https://wpscan.com/vulnerability/' . $id ) . '" target="_blank">';
				$html .= esc_html( $title );
				$html .= '</a>';
			} else {
				$html = esc_html( $title );
			}

			$list[] = $html;
		}

		return $list;
	}
}
