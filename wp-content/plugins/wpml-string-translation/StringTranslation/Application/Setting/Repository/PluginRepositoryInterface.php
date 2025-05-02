<?php

namespace WPML\StringTranslation\Application\Setting\Repository;

interface PluginRepositoryInterface {
	public function getActiveCachePluginName(): string;
	public function shouldShowNoticeThatCachePluginCanBlockAutoregister(): bool;
	public function setNoticeThatCachePluginCanBlockAutoregisterAsDismissed();
}