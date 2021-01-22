<?php
/**
 * Custom Twig Profile dumper for QM.
 *
 * @package NdB\QM_Twig_Profile
 */

namespace Twig\Profiler\Dumper;

use QM_Output_Html;
use Twig\Loader\LoaderInterface;
use Twig\Profiler\Profile;

/**
 * This custom dumper has some extra support for formatting.
 *
 * It adds compatibility with QM's dark mode.
 */
final class Dumper extends BaseDumper {
	/**
	 * The loader used for a template file in the twig environment that produced
	 * the profile.
	 *
	 * @var LoaderInterface
	 */
	private $loader;

	/**
	 * Colors for rendering the profile dump.
	 *
	 * @var array<string, string> $colors
	 */
	private static $colors = array(
		'auto'       => '#fdf',
		'block'      => '#dfd',
		'macro'      => '#ddf',
		'template'   => '#ffd',
		'big'        => '#d44',
		'text-light' => '#fff',
		'text-dark'  => '#000',
	);

	/**
	 * Sets the loader interface.
	 *
	 * Must be the loader that was in the environment at the time the profile
	 * was produced.
	 *
	 * @param LoaderInterface $loader The loader.
	 */
	public function __construct( LoaderInterface $loader ) {
		$this->loader = $loader;
	}

	/**
	 * Returns the content of the profile.
	 *
	 * @param Profile $profile The twig profile to dump.
	 */
	public function dump( Profile $profile ):string {
		return '<pre>' . parent::dump( $profile ) . '</pre>';
	}

	/**
	 * Formats a template to profile html.
	 *
	 * @param Profile $profile The twig profile to dump.
	 * @param string  $prefix Indentation depth.
	 */
	protected function formatTemplate( Profile $profile, $prefix ):string {
		return sprintf( '%s└ <span>%s</span>', $prefix, $this->get_template_filename( $profile ) );
	}

	/**
	 * Maybe formats the filename to a clickable file path.
	 *
	 * @param Profile $profile The Twig profile that the file is in.
	 */
	private function get_template_filename( Profile $profile ):string {
		$template = $profile->getTemplate();
		$source   = $this->loader->getSourceContext( $template );
		$path     = $source->getPath();
		if ( $path && QM_Output_Html::has_clickable_links() ) {
			$template = QM_Output_Html::output_filename( $template, $path, 0, true );
		}
		return $template;
	}

	/**
	 * Formats everything that's not a template (blocks, macros) to profile html.
	 *
	 * @param Profile $profile The twig profile to dump.
	 * @param string  $prefix Indentation depth.
	 */
	protected function formatNonTemplate( Profile $profile, $prefix ):string {
		return sprintf( '%s└ %s::%s(<span style="background-color: %s; color: %s">%s</span>)', $prefix, $this->get_template_filename( $profile ), $profile->getType(), isset( self::$colors[ $profile->getType() ] ) ? self::$colors[ $profile->getType() ] : self::$colors['auto'], self::$colors['text-dark'], $profile->getName() );
	}

	/**
	 * Colors the time to mark everything 20% and up.
	 *
	 * @param Profile $profile The twig profile to dump.
	 * @param string  $percent The relative duration.
	 */
	protected function formatTime( Profile $profile, $percent ):string {
		return sprintf( '<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::$colors['big'] : 'auto', $profile->getDuration() * 1000, $percent );
	}
}
