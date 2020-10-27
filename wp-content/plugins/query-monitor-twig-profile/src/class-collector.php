<?php
/**
 * Query Monitor Collector for twig profiles.
 *
 * @package NdB\QM_Twig_Profile
 */

namespace NdB\QM_Twig_Profile;

/**
 * The Twig Profile collector.
 */
final class Collector extends \QM_Collector {
	/**
	 * Query monitor ID, used for the panel ID.
	 *
	 * @var string $id
	 */
	public $id = 'twig_profile';

	/**
	 * Store of Twig profile objects.
	 *
	 * @var array<int, Environment_Profile> $profiles
	 */
	private $profiles = array();

	/**
	 * Add a twig profile to the store.
	 *
	 * @param Environment_Profile $profile A twig profile.
	 * @return void
	 */
	public function add( Environment_Profile $profile ) {
		$this->profiles[] = $profile;
	}

	/**
	 *  Retrieves all profiles in the store.
	 *
	 * @return array<int, Environment_Profile>
	 */
	public function get_all() {
		return $this->profiles;
	}
}
