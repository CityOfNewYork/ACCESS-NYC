<?php

namespace WPML\TM\Notices;

use WPML\TM\ATE\ClonedSites\Lock;
use WPML\TM\Templates\Notices\AteLocked;

class AteLockNotice implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/**
	 * @var AteLocked
	 */
	private $templateRenderer;

	public function __construct( AteLocked $templateRenderer ) {
		$this->templateRenderer = $templateRenderer;
	}

	public function add_hooks() {
		add_action( 'admin_notices', [ $this, 'ateLockNotice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
	}

	public function enqueueScripts() {
		if ( $this->shouldRender() ) {
			wp_enqueue_script(
				'wpml-tm-ate-lock',
				WPML_TM_URL . '/res/js/ate-api-lock-notification.js',
				[],
				WPML_TM_VERSION,
				true
			);
		}
	}

	public function ateLockNotice() {
		if ( $this->shouldRender() ) {
			$this->renderNotice();
		}
	}

	private function shouldRender() {
		return Lock::isLocked() && $this->shouldDisplayOnCurrentPage();
	}

	private function renderNotice() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->renderAdminNotice();
		} else {
			$this->renderUserNotice();
		}
	}

	private function renderAdminNotice() {
		$model = (object) [
			'title'          => __( 'Site Moved or Copied - Action Required', 'wpml-translation-management' ),
			'intro'          => __( 'Looks like this site is a copy of a different site, or moved to a different URL.', 'wpml-translation-management' ),
			'radio_option_1' => __( 'I moved the site to this new URL', 'wpml-translation-management' ),
			'radio_option_2' => __( 'This is a copy of my original site', 'wpml-translation-management' ),
			'btn_text'       => __( 'Save', 'wpml-translation-management' ),
			'link_text'      => __( 'More details', 'wpml-translation-management' ),
			'allowed_modes'  => apply_filters( 'wpml_ate_locked_allow_site_move_copy', [ 'move' => true, 'copy' => true ] ),
		];

		$this->templateRenderer->renderAdmin( $model );
	}

	private function renderUserNotice() {
		$model = (object) [
			'title' => __( 'Site Moved or Copied - Action Required', 'wpml-translation-management' ),
			'intro' => __( 'Looks like this site is a copy of a different site, or moved to a different URL. Please contact your translation manager to update Translation Management plugin configuration.', 'wpml-translation-management' ),
		];

		$this->templateRenderer->renderUser( $model );
	}

	private function shouldDisplayOnCurrentPage() {
		return $this->shouldDisplayOnScreen( [ 'dashboard' ] ) || $this->shouldDisplayOnPage( $this->getPages() );
	}

	/**
	 * @param array $screens
	 *
	 * @return bool
	 */
	private function shouldDisplayOnScreen( array $screens ) {
		$currentScreen = get_current_screen();
		return $currentScreen instanceof \WP_Screen
			&& in_array( $currentScreen->id, $screens );
	}

	/**
	 * @param array $pages
	 *
	 * @return bool
	 */
	private function shouldDisplayOnPage( array $pages ) {
		return isset( $_GET['page'] ) && in_array( $_GET['page'], $pages );
	}

	/**
	 * @return array|string[]
	 */
	private function getPages() {
		$pages = [
			WPML_PLUGIN_FOLDER . '/menu/languages.php',
			WPML_PLUGIN_FOLDER . '/menu/theme-localization.php',
			WPML_PLUGIN_FOLDER . '/menu/settings.php',
			WPML_PLUGIN_FOLDER . '/menu/support.php',
			WPML_TM_FOLDER . '/menu/settings',
			WPML_TM_FOLDER . '/menu/main.php',
			WPML_TM_FOLDER . '/menu/translations-queue.php',
			WPML_TM_FOLDER . '/menu/string-translation.php',
			WPML_TM_FOLDER . '/menu/settings.php',
		];

		return $pages;
	}
}
