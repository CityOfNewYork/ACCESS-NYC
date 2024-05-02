<?php

/**
 * @deprecated Use \ACFML\Field\Resolver instead
 */
class WPML_ACF_Duplicated_Post extends \ACFML\Field\Resolver {

	/**
	 * @param \WPML_ACF_Processed_Data $processed_data
	 *
	 * @return \WPML_ACF_Field
	 *
	 * @deprecated Use \ACFML\Field\Resolver::run instead
	 */
	public function resolve_field( \WPML_ACF_Processed_Data $processed_data ) {
		return $this->run( $processed_data );
	}

}
