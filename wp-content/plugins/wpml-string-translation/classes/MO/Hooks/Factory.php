<?php

namespace WPML\ST\MO\Hooks;

use IWPML_Action;
use WPML\ST\DB\Mappers\DomainsRepository;
use WPML\ST\MO\File\ManagerFactory;
use WPML\ST\TranslationFile\Sync\FileSync;
use WPML\ST\TranslationFile\UpdateHooksFactory;
use WPML\ST\TranslationFile\Hooks;
use function WPML\Container\make;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * Create hooks.
	 *
	 * @return IWPML_Action[]
	 * @throws \WPML\Auryn\InjectionException Auryn Exception.
	 */
	public function create() {
		$manager = ManagerFactory::create();

		$moFileSync = make(
			Sync::class,
			[
				':fileSync'               => make( FileSync::class, [ ':manager' => ManagerFactory::create() ] ),
				':useFileSynchronization' => [ Hooks::class, 'useFileSynchronization' ],
			]
		);

		return [
			UpdateHooksFactory::create(),
			make( LoadTextDomain::class, [ ':file_manager' => $manager ] ),
			make( CustomTextDomains::class, [
				':file_manager' => $manager,
				':syncMissingFile' => [ $moFileSync, 'syncFile' ],
			] ),
			make( LanguageSwitch::class ),
			make( LoadMissingMOFiles::class ),
			make( PreloadThemeMoFile::class ),
			make( DetectPrematurelyTranslatedStrings::class ),
			$moFileSync,
			make( StringsLanguageChanged::class, [
				':manager'               => $manager,
				':getDomainsByStringIds' => DomainsRepository::getByStringIds(),
			] ),
		];
	}
}
