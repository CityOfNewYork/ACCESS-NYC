<?php

namespace ACFML\Repeater\Sync;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use ACFML\Helper\Fields;
use ACFML\Repeater\Shuffle\Strategy;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class PostHooks implements \IWPML_Backend_Action {

	/**
	 * @var Strategy
	 */
	private $shuffled;

	/**
	 * @var CheckboxCondition
	 */
	private $checkboxCondition;

	public function __construct(
		Strategy $shuffled,
		CheckboxCondition $checkboxCondition
	) {
		$this->shuffled          = $shuffled;
		$this->checkboxCondition = $checkboxCondition;
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onAction( 'acf/add_meta_boxes', 10, 3 )
			->then( spreadArgs( [ $this, 'addMetaBox' ] ) );
		Hooks::onAction( 'acf/add_meta_boxes', 10, 3 )
			->then( spreadArgs( [ $this, 'resetFieldValues' ] ) );
	}

	/**
	 *  @param string   $postType The post type.
	 *  @param \WP_Post $post The post being edited.
	 *  @param array    $fieldGroups The field groups added.
	 */
	public function addMetaBox( $postType, $post, $fieldGroups ) {
		if ( ! $this->checkboxCondition->isMet( $post->ID, $fieldGroups ) ) {
			return;
		}

		CheckboxUI::addMetaBox(
			$this->shuffled->getTrid( $post->ID ),
			$post->post_type
		);
	}

	/**
	 * Resetting field values is far from optimal,
	 * but we cannot find a better solution for now.
	 *
	 * Also, this will be limited to sites with
	 * field groups set to be translated which
	 * is not recommended anymore.
	 *
	 * So it should impact always fewer users.
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/acfml-746/
	 *
	 * @return void
	 */
	public function resetFieldValues() {
		if ( FieldGroup::isTranslatable()) {
			acf_get_store( 'values' )->reset();
		}
	}
}
