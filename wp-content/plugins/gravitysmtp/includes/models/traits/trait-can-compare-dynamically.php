<?php

namespace Gravity_Forms\Gravity_SMTP\Models\Traits;

trait Can_Compare_Dynamically {

	public function compare( $value_a, $value_b, $op ) {
		switch( $op ) {
			default:
			case '=':
				return $value_a == $value_b;
			case '>':
				return $value_a > $value_b;
			case '>=':
				return $value_a >= $value_b;
			case '<':
				return $value_a < $value_b;
			case '<=':
				return $value_a <= $value_b;
			case '!=':
				return $value_a != $value_b;
		}
	}

}