<?php

namespace WPML\TM\Menu\TranslationServices;

class ServiceMapper {
	/**
	 * @param \WPML_TP_Service $service
	 * @param callable         $getActiveServiceId
	 *
	 * @return array
	 */
	public static function map( \WPML_TP_Service $service, $getActiveServiceId ) {
		return [
			'id'                             => $service->get_id(),
			'logo_url'                       => $service->get_logo_preview_url(),
			'name'                           => $service->get_name(),
			'description'                    => $service->get_description(),
			'doc_url'                        => $service->get_doc_url(),
			'active'                         => $service->get_id() === $getActiveServiceId() ? 'active' : 'inactive',
			'rankings'                       => $service->get_rankings(),
			'how_to_get_credentials_desc'    => $service->get_how_to_get_credentials_desc(),
			'how_to_get_credentials_url'     => $service->get_how_to_get_credentials_url(),
			'client_create_account_page_url' => $service->get_client_create_account_page_url(),
			'custom_fields'                  => $service->get_custom_fields(),
			'countries'                      => $service->get_countries(),
			'url'                            => $service->get_url(),
		];
	}

}
