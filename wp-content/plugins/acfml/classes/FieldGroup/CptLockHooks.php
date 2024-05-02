<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\FieldGroup;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;

class CptLockHooks implements \IWPML_Backend_Action {

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * @var \TranslationManagement $tm
	 */
	private $tm;

	public function __construct( \SitePress $sitepress, \TranslationManagement $tm ) {
		$this->sitepress = $sitepress;
		$this->tm        = $tm;
	}

	public function add_hooks() {
		Hooks::onAction( 'admin_init' )
			->then( [ $this, 'disableFieldGroupCptPreferenceOnTheFly' ] );
	}

	/**
	 * We cannot define the `acf-field-group` preference with
	 * the config file because it would break old installations
	 * with a different preference.
	 *
	 * Instead, if the site is running `acf-field-group` with
	 * "DO NOT TRANSLATE" preference, we'll make it "read-only"
	 * on the fly unless it's unlocked.
	 *
	 * @return void
	 */
	public function disableFieldGroupCptPreferenceOnTheFly() {
		$cptUnlockOptions = $this->sitepress->get_setting( 'custom_posts_unlocked_option', [] );
		$cptSyncOptions   = $this->sitepress->get_setting( 'custom_posts_sync_option', [] );

		$isUnlocked        = (bool) Obj::prop( FieldGroup::CPT, $cptUnlockOptions );
		$isNotTranslatable = WPML_CONTENT_TYPE_DONT_TRANSLATE === (int) Obj::propOr( WPML_CONTENT_TYPE_DONT_TRANSLATE, FieldGroup::CPT, $cptSyncOptions );

		if ( ! $isUnlocked && $isNotTranslatable ) {
			$this->tm->settings['custom-types_readonly_config'][ FieldGroup::CPT ] = WPML_CONTENT_TYPE_DONT_TRANSLATE;
		}
	}
}
