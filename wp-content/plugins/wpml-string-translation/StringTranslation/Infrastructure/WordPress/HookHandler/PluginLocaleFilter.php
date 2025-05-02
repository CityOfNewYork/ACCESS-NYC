<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\StringGettext\Repository\LoadedTextdomainRepositoryInterface;

class PluginLocaleFilter extends AbstractFilterHookHandler {
	const FILTER_NAME = 'plugin_locale';
	const FILTER_ARGS = 2;
	const FILTER_PRIORITY = 0;

	/** @var LoadedTextdomainRepositoryInterface */
	private $loadedTextdomainRepository;

	public function __construct(
		LoadedTextdomainRepositoryInterface $loadedTextdomainRepository
	) {
		$this->loadedTextdomainRepository = $loadedTextdomainRepository;
	}

	protected function onFilter( ...$args ) {
		list( $locale, $domain ) = $args;
		$this->loadedTextdomainRepository->addPluginDomain( $domain );

		return $locale;
	}
}
