<?php

namespace Gravity_Forms\Gravity_SMTP\Pages;

use Gravity_Forms\Gravity_SMTP\Assets\Assets_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Page_Service_Provider extends Service_Provider {

	const ADMIN_PAGE = 'admin_page';

	protected $plugin_url;


	public function __construct( $plugin_url ) {
		$this->plugin_url = $plugin_url;
	}

	public function register( Service_Container $container ) {
		$container->add( self::ADMIN_PAGE, function() {
			return new Admin_Page();
		});
	}

	public function init( Service_Container $container ) {
		add_action( 'admin_menu', function() use ( $container ) {
			$container->get( self::ADMIN_PAGE )->admin_pages();
		} );
		add_filter( 'plugin_action_links', array( $this, 'plugin_pages_links' ), 10, 2 );
	}

	public function plugin_pages_links( $links, $file ) {
		if ( $file != GF_GRAVITY_SMTP_PLUGIN_BASENAME ) {
			return $links;
		}

		if ( ! is_array( $links ) ) {
			$links = array();
		}

		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php' ) ) . '?page=gravitysmtp-settings">' . esc_html__( 'Settings', 'gravitysmtp' ) . '</a>' );

		return $links;
	}

}
