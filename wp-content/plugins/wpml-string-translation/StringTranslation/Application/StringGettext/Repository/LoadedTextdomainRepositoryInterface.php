<?php

namespace WPML\StringTranslation\Application\StringGettext\Repository;

interface LoadedTextdomainRepositoryInterface {
	public function addThemeDomain( string $domain );
	/* @return string[] */
	public function getThemeDomains(): array;
	public function addPluginDomain( string $domain );
	/* @return string[] */
	public function getPluginDomains(): array;
}