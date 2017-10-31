<?php
/**
 * @author OnTheGo Systems
 */

class WPML_ST_MO_Scan_Notices {
	const NOTICES_GROUP               = 'wpml-st-mo-scan';
	const NOTICES_MO_SCANNING         = 'mo-scanning';
	const NOTICES_MO_LOADING_DISABLED = 'mo-loading-disabled';

	private $disable_mo_loading;
	private $notices;
	private $queue;
	private $wpml_wp_api;

	/**
	 * WPML_ST_MO_Scan_Notices constructor.
	 *
	 * @param bool             $disable_mo_loading
	 * @param WPML_ST_MO_Queue $queue
	 * @param WPML_Notices     $notices
	 * @param WPML_WP_API      $wpml_ap_api
	 *
	 * @internal param WPML_Notices $notices
	 */
	public function __construct( $disable_mo_loading, WPML_ST_MO_Queue $queue, WPML_Notices $notices, WPML_WP_API $wpml_ap_api ) {
		$this->disable_mo_loading = $disable_mo_loading;
		$this->queue              = $queue;
		$this->notices            = $notices;
		$this->wpml_wp_api        = $wpml_ap_api;
	}

	public function init_notices() {
		if ( $this->disable_mo_loading ) {
			$this->handle_queue_notices();
		} else {
			$this->remove_all_notices();
		}
	}

	private function handle_queue_notices() {
		$data     = array();

		if ( $this->queue->is_completed() ) {
			$messages              = array();
			$display_second_notice = true;

			$processed_files = $this->queue->get_processed();
			if ( $processed_files ) {
				$data[] = '<ul><li>' . implode( '</li><li>', $processed_files ) . '</li></ul>';
			} else {
				$data[] = 'none';
			}
			$messages[] = _x( 'WPML has completed importing the content of .mo files to the String Translation table.', 'MO Import completed 1/3', 'wpml-string-translation' );
			$messages[] = _x( 'From now on, the following .mo files will not load: %s', 'MO Import completed 2/3', 'wpml-string-translation' );
			$messages[] = _x( 'When you update plugins or the theme, WPML will rescan their translation files.', 'MO Import completed 3/3', 'wpml-string-translation' );
			$this->queue->mark_as_finished();
		} elseif ( $this->queue->is_processing() ) {
			$messages              = array();
			$display_second_notice = true;

			$data[]     = '<strong>' . $this->queue->get_pending() . '</strong>';
			$messages[] = _x( 'WPML is currently importing translations from the .mo files of themes and plugins into the String Translation table.', 'MO Import im progress 1/3', 'wpml-string-translation' );
			$messages[] = _x( 'Right now, there are still %s .mo files to scan and import.', 'MO Import im progress 2/3', 'wpml-string-translation' );
			$messages[] = _x( "This is happening in small pieces, so that the import doesn't impact the speed of your site too much.", 'MO Import im progress 3/3', 'wpml-string-translation' );
		} else {
			$messages   = array();
			$messages[] = _x( "WPML will gradually import translations from your site's .mo files into the String Translation table. When this import is complete, WPML will prevent the .mo files from loading.", 'MO Loading disabled 1/2', 'wpml-string-translation' );
			$messages[] = _x( "While this process is ongoing, you are not going to feel a speed improvement. Once it's ready, pages will load faster.", 'MO Loading disabled 2/2', 'wpml-string-translation' );
			$message    = '<p>' . implode( '</p><p>', $messages ) . '</p>';
			$notice     = $this->notices->create_notice( self::NOTICES_MO_LOADING_DISABLED, $message, self::NOTICES_GROUP );

			$notice->set_hide_if_notice_exists( self::NOTICES_MO_SCANNING, self::NOTICES_GROUP );

			$this->add_notice( $notice );

			return;
		}


		$message = vsprintf( '<p>' . implode( '</p><p>', $messages ) . '</p>', $data );

		$notice = $this->notices->create_notice( self::NOTICES_MO_SCANNING, $message, self::NOTICES_GROUP );

		if ( $display_second_notice ) {
			$this->remove_first_notice();
			$notice->reset_dismiss();
		}
		$this->add_notice( $notice );
	}

	/**
	 * @param WPML_Notice $notice
	 */
	private function add_notice( WPML_Notice $notice ) {
		$restricted_pages = array(
			WPML_PLUGIN_FOLDER . '/menu/languages.php',
			WPML_PLUGIN_FOLDER . '/menu/theme-localization.php',
		);

		foreach ( $restricted_pages as $restricted_page ) {
			$notice->add_restrict_to_page( $restricted_page );
		}

		$notice->set_css_class_types( 'info' );
		$notice->set_dismissible( true );
		$notice->add_display_callback( array( __CLASS__, 'only_display_notice_if_this_class_exists' ) );

		$this->notices->add_notice( $notice );
	}

	private function remove_first_notice() {
		$first_notice = $this->notices->get_notice( self::NOTICES_MO_LOADING_DISABLED, self::NOTICES_GROUP );
		if ( $first_notice && ! $this->notices->is_notice_dismissed( $first_notice ) ) {
			$this->notices->dismiss_notice( $first_notice, true );
		}
	}

	private function remove_all_notices() {
		$notices_to_remove = array(
			self::NOTICES_MO_LOADING_DISABLED,
			self::NOTICES_MO_SCANNING,
		);

		foreach ( $notices_to_remove as $notice_id ) {
			if ( $this->notices->get_notice( $notice_id, self::NOTICES_GROUP ) ) {
				$this->notices->remove_notice( self::NOTICES_GROUP, $notice_id );
			}
		}
	}

	/** @return bool */
	public static function only_display_notice_if_this_class_exists() {
		return true;
	}
}
