<?php
/**
 * Matches a profile to the environment it was produced in.
 *
 * @package NdB\QM_Twig_Profile
 */

namespace NdB\QM_Twig_Profile;

use Twig\Environment;
use Twig\Profiler\Profile;

/**
 * Data object to combine environments and profiles.
 */
class Environment_Profile {
	/**
	 * The Twig environment the profile was produced in.
	 *
	 * @var Environment
	 */
	public $environment;

	/**
	 * The Twig profile.
	 *
	 * @var Profile
	 */
	public $profile;

	/**
	 * Sets the data object properties.
	 *
	 * @param Environment $twig The active Twig environment.
	 * @param Profile     $profile The profile.
	 */
	public function __construct( Environment $twig, Profile $profile ) {
		$this->environment = $twig;
		$this->profile     = $profile;
	}
}
