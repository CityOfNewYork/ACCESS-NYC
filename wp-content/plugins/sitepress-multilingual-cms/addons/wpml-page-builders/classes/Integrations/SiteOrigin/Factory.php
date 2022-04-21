<?php

namespace WPML\PB\SiteOrigin;

class Factory {

	public function create() {
		$loader = new \WPML_Action_Filter_Loader();
		$loader->load(
			[
				HandleCustomFieldsFactory::class,
				Config\Factory::class,
			]
		);

		$nodes        = new TranslatableNodes();
		$dataSettings = new DataSettings();

		$stringRegistrationFactory = new \WPML_String_Registration_Factory( $dataSettings->get_pb_name() );
		$stringRegistration        = $stringRegistrationFactory->create();

		return new \WPML_Page_Builders_Integration(
			new RegisterStrings( $nodes, $dataSettings, $stringRegistration ),
			new UpdateTranslation( $nodes, $dataSettings ),
			$dataSettings
		);
	}

}
