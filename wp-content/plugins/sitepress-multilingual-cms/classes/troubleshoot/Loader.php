<?php

namespace WPML\TM\Troubleshooting;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Nonce;
use WPML\Core\WP\App\Resources;
use WPML\Media\Option;
use WPML\TM\ATE\AutoTranslate\Endpoint\CancelJobs;
use WPML\TM\ATE\ClonedSites\Lock;
use WPML\TM\ATE\ClonedSites\SecondaryDomains;
use WPML\TM\ATE\Hooks\JobActionsFactory;
use WPML\LanguageSwitcher\LsTemplateDomainUpdater;
use WPML\TM\Troubleshooting\Endpoints\ATESecondaryDomains\EnableSecondaryDomain;

class Loader implements \IWPML_Backend_Action {

	public function add_hooks() {

		add_action( 'after_setup_complete_troubleshooting_functions', [ $this, 'render' ], 7 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
	}

	public function render() {
		echo '<div id="wpml-troubleshooting-container" style="margin: 5px 0;"></div>';
	}

	public function enqueueScripts( $hook ) {
		if ( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' === $hook ) {
			$enqueue = Resources::enqueueApp( 'troubleshooting' );
			$enqueue(
				[
					'name' => 'troubleshooting',
					'data' => [
						'refreshLicense' => [
							'nonce' => Nonce::create( 'update_site_key_wpml' ),
						],
						'isATELocked'    => Lock::isLocked(),
						'endpoints'      => [
							'cancelJobs'              => CancelJobs::class,
							'lsTemplatesUpdateDomain' => LsTemplateDomainUpdater::class,
							'enableSecondaryDomain'   => EnableSecondaryDomain::class,
						],
					],
				]
			);
		}
	}
}