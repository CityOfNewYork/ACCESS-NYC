<?php

namespace WPML\MediaTranslation\MediaCollector;

interface PathResolverInterface {

	/**
	 * @param mixed $data
	 * @return string|int
	 */
	public function getValue( $data );

	/**
	 * @param mixed $data
	 * @return string|array|object
	 */
	public function resolvePath( $data );

}

