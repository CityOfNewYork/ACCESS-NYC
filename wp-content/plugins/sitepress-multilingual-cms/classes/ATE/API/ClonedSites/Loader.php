<?php

namespace WPML\TM\ATE\ClonedSites;

use WPML\Core\WP\App\Resources;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use WPML\TM\ATE\ClonedSites\Endpoints\GetCredits as ClonedSitesGetCredits;
use WPML\TM\ATE\ClonedSites\Endpoints\Copy;
use WPML\TM\ATE\ClonedSites\Endpoints\Move;
use WPML\TM\ATE\ClonedSites\Endpoints\CopyWithCredits;
use WPML\TM\ATE\ClonedSites\Lock;
use WPML\TM\Templates\Notices\AteLocked;
use WPML\LIB\WP\Hooks;
use WPML\UIPage;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\spreadArgs;

class Loader implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var Lock */
	private $lock;

	public function __construct( Lock $lock ) {
		$this->lock = $lock;
	}

	public function add_hooks() {
		$displayPlaceholder = function () {
			if ( $this->shouldRender() ) {
				echo '<div id="wpml-tm-ate-lock-notice"></div>';
			}
		};

		Hooks::onAction( 'admin_notices' )
		     ->then( $displayPlaceholder );

		Hooks::onFilter( 'wpml_tm_dashboard_notices' )
		     ->then( spreadArgs( Lst::append( $displayPlaceholder ) ) );

		Hooks::onAction( 'admin_enqueue_scripts' )
		     ->then( function () {
			     if ( $this->shouldRender() ) {
				     $fn = Resources::enqueueApp( 'ate/clonedSites' );
				     $fn( $this->getData() );
			     }
		     } );
	}

	public function getData() {
		$lockData = $this->lock->getLockData();

		$urlCurrentlyRegisteredInAMS = Obj::prop( 'urlCurrentlyRegisteredInAMS', $lockData );
		$urlUsedToMakeRequest        = Obj::prop( 'urlUsedToMakeRequest', $lockData );

		return [
			'name' => 'ate_cloned_sites',
			'data' => [
				'hasRightToHandle' => User::isAdministrator(),
				'endpoints'        => [
					'move'                  => Move::class,
					'copy'                  => Copy::class,
					'copyWithCredits'       => CopyWithCredits::class,
					'clonedSitesGetCredits' => ClonedSitesGetCredits::class,
				],
				'urls'             => [
					'toolsOnOldSite' => $urlCurrentlyRegisteredInAMS . '/wp-admin/' . UIPage::getTMATE() . '&widget_action=open_sites&force_code=1',
				],
				'settings'         => [
					'allowedModes'                    => apply_filters( 'wpml_ate_locked_allow_site_move_copy', [
						'move' => true,
						'copy' => true
					] ),
					'urlCurrentlyRegisteredInAMS'     => $urlCurrentlyRegisteredInAMS,
					'urlUsedToMakeRequest'            => Obj::prop( 'urlUsedToMakeRequest', $lockData ),
					'isOriginalSiteMovedToAnotherURL' => $lockData['identicalUrlBeforeMovement'],
				],
			],
		];
	}

	private function shouldRender() {
		return Lock::isLocked() && $this->shouldDisplayOnCurrentPage();
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
		$currentScreen = \get_current_screen();

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
