<?php

namespace ACFML\Repeater\Sync;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\Fields;
use ACFML\Repeater\Shuffle\Strategy;
use WPML\API\Sanitize;
use WPML\LIB\WP\Hooks;

class TermHooks implements \IWPML_Backend_Action {

	/**
	 * @var Strategy
	 */
	private $shuffled;

	public function __construct( Strategy $shuffled ) {
		$this->shuffled = $shuffled;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return (int) Sanitize::stringProp( 'tag_ID', $_GET ); // phpcs:ignore
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		$id = $this->getId();
		if ( ! $id ) {
			return;
		}
		$id     = 'term_' . $id;
		$fields = get_field_objects( $id );

		if ( $fields
			&& ( Fields::containsType( $fields, 'repeater' ) || Fields::containsType( $fields, 'flexible_content' ) )
			&& in_array( Mode::getForFieldableEntity( 'taxonomy' ), [ Mode::ADVANCED, Mode::MIXED ], true )
			&& $this->shuffled->isOriginal( $id )
			&& $this->shuffled->hasTranslations( $id )
		) {
			Hooks::onAction( 'admin_enqueue_scripts' )
				->then( [ $this, 'displayCheckbox' ] );
		}
	}

	public function displayCheckbox() {
		Hooks::onAction( 'category_edit_form', 9 )
			->then( function() {
				CheckboxUI::render(
					$this->shuffled->getTrid( 'term_' . $this->getId() ),
					true
				);
			} );
	}
}
