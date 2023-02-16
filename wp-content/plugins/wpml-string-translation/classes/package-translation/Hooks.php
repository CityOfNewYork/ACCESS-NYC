<?php

namespace WPML\ST\PackageTranslation;

class Hooks implements \IWPML_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		/**
		 * @see Assign::stringsFromDomainToExistingPackage()
		 */
		add_action(
			'wpml_st_assign_strings_from_domain_to_existing_package',
			Assign::class . '::stringsFromDomainToExistingPackage',
			10,
			2
		);

		/**
		 * @see Assign::stringsFromDomainToNewPackage()
		 */
		add_action(
			'wpml_st_assign_strings_from_domain_to_new_package',
			Assign::class . '::stringsFromDomainToNewPackage',
			10,
			2
		);
	}
}
