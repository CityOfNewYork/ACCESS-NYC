<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;
use ACFML\Strings\Helper\ContentTypeLabels;
use WPML\FP\Obj;

class Cpt extends Entity {

	/** @var string $idKey */
	protected $idKey = 'post_type';

	/** @var array $labelsInDataMap */
	private $labelsInDataMap = [
		'post_type'        => 'post_type',
		'enter_title_here' => 'enter_title_here',
	];

	/** @var array $labelsInDataMap */
	private $labelsinContextMap = [
		'description' => 'description',
	];

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForCpt();
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
