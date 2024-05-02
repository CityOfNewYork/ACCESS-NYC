<?php

namespace ACFML\Repeater\Sync;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\Fields;
use ACFML\Repeater\Shuffle\Strategy;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\API\Sanitize;
use WPML\LIB\WP\Hooks;

class OptionPageHooks implements \IWPML_Backend_Action {

	const SCREEN_ID = 'acf_options_page';

	const HOOK_PRIORITY_BEFORE = 9;
	const HOOK_PRIORITY_AFTER  = 11;

	/**
	 * @var Strategy
	 */
	private $shuffled;

	/**
	 * @var CheckboxCondition
	 */
	private $checkboxCondition;

	/** @var string|null */
	private $optionsPageId;

	public function __construct(
		Strategy $shuffled,
		CheckboxCondition $checkboxCondition
	) {
		$this->shuffled          = $shuffled;
		$this->checkboxCondition = $checkboxCondition;
	}

	/**
	 * @return null|string
	 */
	private function getId() {
		// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$pageSlug = Sanitize::stringProp( 'page', $_REQUEST );
		if ( ! $pageSlug ) {
			return null;
		}
		$page = acf_get_options_page( $pageSlug );
		return is_array( $page ) ? Obj::prop( 'post_id', $page ) : null;
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		$this->optionsPageId = $this->getId();
		if ( ! $this->optionsPageId ) {
			return;
		}

		Hooks::onAction( 'acf/input/admin_head', self::HOOK_PRIORITY_BEFORE )
			->then( [ $this, 'addMetaBox' ] );
		Hooks::onAction( 'acf/input/admin_head', self::HOOK_PRIORITY_AFTER )
			->then( [ $this, 'verifyMetaBox' ] );
	}

	public function addMetaBox() {
		CheckboxUI::addMetaBox(
			$this->shuffled->getTrid( $this->optionsPageId ),
			self::SCREEN_ID
		);
	}

	public function verifyMetaBox() {
		global $wp_meta_boxes;
		
		if ( ! $wp_meta_boxes ) {
			return;
		}

		$metaBoxes = Lst::flattenToDepth(
			2,
			Obj::propOr( [], self::SCREEN_ID, $wp_meta_boxes )
		);

		$fieldGroups = wpml_collect( $metaBoxes )
			->map( function( $metaBox ) {
				if ( Str::startsWith( 'acf-group_', $metaBox['id'] ) ) {
					return Obj::pathOr( false, [ 'args', 'field_group' ], $metaBox );
				}
				return false;
			} )
			->filter( Logic::isTruthy() )
			->toArray();

		if ( ! $this->checkboxCondition->isMet( $this->optionsPageId, $fieldGroups ) ) {
			CheckboxUI::removeMetaBox( self::SCREEN_ID );
		}
	}

}
