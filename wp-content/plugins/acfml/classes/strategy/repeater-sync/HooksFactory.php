<?php

namespace ACFML\Repeater\Sync;

use ACFML\Repeater\Shuffle\OptionsPage;
use ACFML\Repeater\Shuffle\Post;
use ACFML\Repeater\Shuffle\Resolver;
use ACFML\Repeater\Shuffle\Term;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Deferred_Action_Loader {

	/**
	 * @return string
	 */
	public function get_load_action() {
		return 'wp_loaded';
	}

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		$shuffled = Resolver::getStrategy();
		if ( ! $shuffled ) {
			return [];
		}

		$fieldState       = new \ACFML\FieldState( $shuffled );
		$customFieldsSync = new \WPML_ACF_Custom_Fields_Sync( $fieldState );

		$hooks = [
			$fieldState,
			$customFieldsSync,
			new \WPML_ACF_Repeater_Shuffle( $shuffled, $fieldState ),
			new CheckboxHooks( $shuffled ),
		];

		$checkboxCondition = new CheckboxCondition( $shuffled );

		if ( $shuffled instanceof Post ) {
			$hooks[] = new PostHooks( $shuffled, $checkboxCondition );
		} elseif ( $shuffled instanceof Term ) {
			$hooks[] = new TermHooks( $shuffled );
		} elseif ( $shuffled instanceof OptionsPage ) {
			$hooks[] = new OptionPageHooks( $shuffled, $checkboxCondition );
		}

		return $hooks;
	}
}
