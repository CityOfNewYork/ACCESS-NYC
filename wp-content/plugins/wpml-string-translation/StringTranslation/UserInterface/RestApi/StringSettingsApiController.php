<?php
namespace WPML\StringTranslation\UserInterface\RestApi;

use WPML\Rest\Adaptor;
use \WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use \WPML\StringTranslation\Application\Setting\Repository\PluginRepositoryInterface;

class StringSettingsApiController extends AbstractController {

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var PluginRepositoryInterface */
	private $pluginRepository;

	public function __construct(
		Adaptor                     $adaptor,
		SettingsRepositoryInterface $settingsRepository,
		PluginRepositoryInterface   $pluginRepository
	) {
		parent::__construct( $adaptor );
		$this->settingsRepository = $settingsRepository;
		$this->pluginRepository = $pluginRepository;
	}

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'strings/settings',
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'post' ],
					'args'     => [
						'autoregisterType' => [
							'type'    => 'integer',
							'default' => 0,
						],
						'shouldRegisterBackendStrings' => [
							'type'    => 'integer',
							'default' => 0,
						],
						'setNoticeThatCachePluginCanBlockAutoregisterAsDismissed' => [
							'type'    => 'integer',
							'default' => 0,
						]
					]
				]
			],
			[
				'route' => 'strings/settings',
				'args'  => [
					'methods'  => 'GET',
					'callback' => [ $this, 'get' ],
				],
			],
		];
	}

	/**
	 * @return array
	 */
	public function post( \WP_REST_Request $request ) {
		$autoregisterType             = $request->get_param( 'autoregisterType' );
		$shouldRegisterBackendStrings = (bool) $request->get_param( 'shouldRegisterBackendStrings' );
		$shouldShowNoticeThatCachePluginCanBlockAutoregister = (bool) $request->get_param('shouldShowNoticeThatCachePluginCanBlockAutoregister');

		if ( ! is_null( $autoregisterType ) ) {
			$this->settingsRepository->setAutoregisterStringsTypeSetting( $autoregisterType );
		}

		$this->settingsRepository->setShouldRegisterBackendStringsSetting( $shouldRegisterBackendStrings );

		if ( ! $shouldShowNoticeThatCachePluginCanBlockAutoregister ) {
			$this->pluginRepository->setNoticeThatCachePluginCanBlockAutoregisterAsDismissed();
		}

		return [];
	}

	/**
	 * @return array
	 */
	public function get( \WP_REST_Request $request ) {
		global $sitepress;
		$autoregisterAllowedLanguages = array_values(
			array_map(
				function( $data ) use ( $sitepress ) {
					return [
						'name' => $data['display_name'],
						'url'  => $sitepress->language_url( $data['code'] ),
					];
				},
				array_filter(
					$sitepress->get_active_languages(),
					function( $data ) {
						return $data['code'] !== 'en';
					}
				)
			)
		);

		return [
			'autoregisterType'             => $this->settingsRepository->getAutoregisterStringsTypeSetting(),
			'shouldRegisterBackendStrings' => $this->settingsRepository->getShouldRegisterBackendStringsSetting() ? 1 : 0,
			'autoregisterAllowedLanguages' => $autoregisterAllowedLanguages,
			'activeCachePluginName'        => $this->pluginRepository->shouldShowNoticeThatCachePluginCanBlockAutoregister()
				? $this->pluginRepository->getActiveCachePluginName()
				: '',
			'shouldShowNoticeThatCachePluginCanBlockAutoregister' => $this->pluginRepository->shouldShowNoticeThatCachePluginCanBlockAutoregister(),
		];
	}
}