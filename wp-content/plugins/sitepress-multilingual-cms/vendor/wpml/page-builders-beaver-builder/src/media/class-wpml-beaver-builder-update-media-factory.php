<?php

class WPML_Beaver_Builder_Update_Media_Factory implements IWPML_PB_Media_Update_Factory {


	public function create() {
		global $sitepress;

		$media_translate = new WPML_Page_Builders_Media_Translate(
			new WPML_Translation_Element_Factory( $sitepress ),
			new WPML_Media_Image_Translate( $sitepress, new WPML_Media_Attachment_By_URL_Factory() )
		);

		return new WPML_Page_Builders_Update_Media(
			new WPML_Page_Builders_Update( new WPML_Beaver_Builder_Data_Settings_For_Media() ),
			new WPML_Translation_Element_Factory( $sitepress ),
			new WPML_Beaver_Builder_Media_Nodes_Iterator(
				new WPML_Beaver_Builder_Media_Node_Provider( $media_translate )
			),
			new WPML_Page_Builders_Media_Usage( $media_translate, new WPML_Media_Usage_Factory() )
		);
	}
}
