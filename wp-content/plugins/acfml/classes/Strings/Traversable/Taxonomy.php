<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;
use ACFML\Strings\Helper\ContentTypeLabels;
use WPML\FP\Obj;

class Taxonomy extends Entity {

	/** @var string $idKey */
	protected $idKey = 'taxonomy';

	/** @var array $labelsInDataMap */
	private $labelsInDataMap = [
		'taxonomy' => 'taxonomy',
	];

	/** @var array $labelsInDataMap */
	private $labelsinContextMap = [
		'description' => 'description',
	];

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForTaxonomy();
	}

	/**
	 * Turn the provided data into a transformable array, if needed.
	 *
	 * @param  array $data
	 * @param  array $context
	 *
	 * @return array
	 */
	protected function prepareData( $data, $context ) {
		return array_merge(
			Obj::propOr( Obj::propOr( [], 'labels', $data ), 'labels', $context ),
			ContentTypeLabels::getLabelsInData( $data, $this->labelsInDataMap ),
			ContentTypeLabels::getLabelsInContext( $data, $context, $this->labelsinContextMap )
		);
	}

}
