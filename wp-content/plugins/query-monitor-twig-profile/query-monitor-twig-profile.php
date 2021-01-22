<?php
/**
 * Plugin Name:  Query monitor Twig profile
 * Description:  Displays Twig profiler output in Query Monitor. Automatically works with Timber.
 * Version:      1.3.1
 * Plugin URI:   https://github.com/NielsdeBlaauw/query-monitor-twig-profile
 * Author:       Niels de Blaauw
 * Author URI:   https://actd.nl/
 * Text Domain:  ndb_qm_twig
 * Requires PHP: 7.0.0
 *
 * @package NdB\QM_Twig_Profile
 */

namespace NdB\QM_Twig_Profile;

use QM_Collectors;
use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

/**
 * Adds our profile collector to Query Monitor.
 *
 * @param array<string, \QM_Collector> $collectors Query Monitors collectors.
 * @return array<string, \QM_Collector>
 */
function register_collector( array $collectors ) {
	require_once __DIR__ . '/src/class-collector.php';
	require_once __DIR__ . '/src/class-environment-profile.php';
	$collectors['twig_profile'] = new Collector();
	return $collectors;
}

add_filter( 'qm/collectors', __NAMESPACE__ . '\\register_collector', 20, 1 );

/**
 * Renders the twig profile query monitor panel.
 *
 * @param array<string, \QM_Output> $output Query monitors prepared output.
 * @return array<string, \QM_Output>
 */
function render( array $output ) {
	$collector = \QM_Collectors::get( 'twig_profile' );
	if ( $collector instanceof Collector ) {
		require_once __DIR__ . '/src/class-output.php';
		$output['twig_profile'] = new Output( $collector );
	}
	return $output;
}

add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\render' );

/**
 * Automatically collects twig profiles from timber.
 *
 * @param \Twig\Environment $twig Timbers twig instance.
 * @return \Twig\Environment
 */
function collect_timber( Environment $twig ):Environment {
	return collect( $twig );
}

add_filter( 'timber/twig', __NAMESPACE__ . '\\collect_timber' );
add_filter( 'clarkson_twig_environment', __NAMESPACE__ . '\\collect_timber' );

/**
 * Adds twig profile collection to a Twig instance.
 *
 * @param \Twig\Environment $twig A Twig instance.
 * @return \Twig\Environment
 */
function collect( Environment $twig ):Environment {
	if ( ! class_exists( 'QM_Collectors' ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Attempted to profile a Twig instance without Query Monitor available. Enable Query Monitor first.', 'ndb_qm_twig' ), '1.1.0' );
		return $twig;
	}
	$profile             = new Profile();
	$environment_profile = new Environment_Profile( $twig, $profile );
	$twig->addExtension( new ProfilerExtension( $profile ) );
	$collector = QM_Collectors::get( 'twig_profile' );
	if ( $collector instanceof Collector ) {
		$collector->add( $environment_profile );
	}
	return $twig;
}

/**
 * Enqueues assets required for interactive content.
 *
 * @return void
 */
function enqueue_scripts() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugin_data = get_plugin_data( __FILE__ );
	wp_enqueue_script( 'qm-twig-profile', plugin_dir_url( __FILE__ ) . 'assets/save.js', array(), $plugin_data['Version'], false );
	wp_enqueue_script( 'qm-twig-profile-component', plugin_dir_url( __FILE__ ) . 'assets/twig-profile/dist/twig-profile.js', array(), $plugin_data['Version'], false );
	wp_localize_script(
		'qm-twig-profile-component',
		'qm_twig_profile_l10n',
		array(
			'strings' => array(
				'save_current'    => __( 'Save current request', 'ndb_qm_twig' ),
				'saved'           => __( 'The current request has been saved!', 'ndb_qm_twig' ),
				'controls'        => __( 'Controls', 'ndb_qm_twig' ),
				'viewing_profile' => __( 'Viewing profile:', 'ndb_qm_twig' ),
				'profile_name'    => __( 'Profile name', 'ndb_qm_twig' ),
				'select_profile'  => __( 'Select a (saved) profile:', 'ndb_qm_twig' ),
				'current_request' => __( 'Current request', 'ndb_qm_twig' ),
				'view'            => __( 'View', 'ndb_qm_twig' ),
				'remove'          => __( 'Remove', 'ndb_qm_twig' ),
				'clear_all'       => __( 'Clear all saved profiles', 'ndb_qm_twig' ),
			),
		)
	);
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 110 );
