<?php

class WPML_TM_Word_Count_Records_Factory {

	/**
	 * @return \WPML_TM_Word_Count_Records
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		return \WPML\Container\make(
			'\WPML_TM_Word_Count_Records',
			[
				':package_records' => \WPML\Container\make( '\WPML_ST_Word_Count_Package_Records' ),
				':string_records'  => \WPML\Container\make( '\WPML_ST_Word_Count_String_Records' ),
			]
		);
	}
}
