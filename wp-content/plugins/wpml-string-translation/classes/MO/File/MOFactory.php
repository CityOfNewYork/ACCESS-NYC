<?php

namespace WPML\ST\MO\File;

class MOFactory {
	/**
	 * @return \MO
	 */
	public function createNewInstance() {
		return new \MO();
	}

}