<?php

namespace Gravity_Forms\Gravity_SMTP\Models\Hydrators;

class Hydrator_Amazon implements Hydrator {

	public function hydrate( $row ) {
		$row['service'] = 'amazon';

		return $row;
	}

}