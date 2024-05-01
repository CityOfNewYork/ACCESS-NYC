<?php

namespace WPML\PB\Elementor\Config\DynamicElements\EssentialAddons;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;

/**
 * @see https://essential-addons.com/elementor/docs/creative-elements/content-timeline/
 */
class ContentTimeline {

	/**
	 * @return array
	 */
	public static function get() {
		// $isEAContentTimeline :: array -> bool
		$isEAContentTimeline = Relation::propEq( 'widgetType', 'eael-content-timeline' );

		// $contentTimelineLinksLens :: callable -> callable -> mixed
		$contentTimelineLinksLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensMappedProp( 'eael_coustom_content_posts' ),
			Obj::lensPath( [ '__dynamic__', 'eael_read_more_text_link' ] )
		);

		return [ $isEAContentTimeline, $contentTimelineLinksLens, 'popup', 'popup' ];
	}
}
