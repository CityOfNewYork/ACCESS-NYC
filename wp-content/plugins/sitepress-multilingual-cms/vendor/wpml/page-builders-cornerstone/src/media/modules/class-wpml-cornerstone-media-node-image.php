<?php

class WPML_Cornerstone_Media_Node_Image extends WPML_Cornerstone_Media_Node_With_URLs {

	protected function get_keys() {
		return array(
			'image_src',
		);
	}
}
