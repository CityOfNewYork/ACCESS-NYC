<?php

namespace ACFML\Repeater\Sync;

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use ACFML\Repeater\Shuffle\Strategy;

class CheckboxCondition {

	/**
	 * @var Strategy
	 */
	private $shuffled;

	public function __construct( Strategy $shuffled ) {
		$this->shuffled = $shuffled;
	}

	/**
	 * @param  string|int $objectId
	 * @param  array      $fieldGroups
	 *
	 * @return bool
	 */
	public function isMet( $objectId, $fieldGroups ) {
		if ( ! in_array( Mode::getForFieldGroups( $fieldGroups ), [ Mode::ADVANCED, Mode::MIXED ], true ) ) {
			return false;
		}

		if (
			! $this->shuffled->isOriginal( $objectId )
			|| ! $this->shuffled->hasTranslations( $objectId )
		) {
			return false;
		}

		$groupHasFieldOfTypes = function( $groupId ) {
			return FieldGroup::hasFieldOfTypes( $groupId, [ 'repeater', 'flexible_content' ] );
		};

		return (bool) wpml_collect( $fieldGroups )
			->pluck( 'ID' )
			->first( $groupHasFieldOfTypes );
	}

}
