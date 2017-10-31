<?php

class WPML_Beaver_Builder_Integration_Factory {

	public function create() {
		$nodes = new WPML_Beaver_Builder_Translatable_Nodes();
		$data_settings = new WPML_Beaver_Builder_Data_Settings();

		$string_registration_factory = new WPML_String_Registration_Factory( $data_settings->get_pb_name() );
		$string_registration = $string_registration_factory->create();
		
		$register_strings = new WPML_Beaver_Builder_Register_Strings( $nodes, $data_settings, $string_registration );
		$update_translation = new WPML_Beaver_Builder_Update_Translation( $nodes, $data_settings );

		return new WPML_Page_Builders_Integration( $register_strings, $update_translation, $data_settings );
	}
}