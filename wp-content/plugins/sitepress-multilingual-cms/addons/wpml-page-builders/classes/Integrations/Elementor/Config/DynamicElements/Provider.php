<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

class Provider {

	/**
	 * @return array
	 */
	public static function get() {
		return [
			EssentialAddons\ContentTimeline::get(),
			LoopGrid::get(),
			LoopCarousel::get(),
			Hotspot::get(),
			Popup::get(),
			IconList::get(),
			FormPopup::get(),
			WooProduct::get( 'title' ),
			WooProduct::get( 'short-description' ),
		];
	}
}
