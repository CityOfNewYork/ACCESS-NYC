<?php

use WPML\Setup\Option;

/**
 * @author OnTheGo Systems
 */
class WPML_Requirements_Notification {
	/**
	 * @var \IWPML_Template_Service
	 */
	private $template_service;

	/**
	 * WPML_Requirements_Notification constructor.
	 *
	 * @param IWPML_Template_Service $template_service
	 */
	public function __construct( IWPML_Template_Service $template_service ) {
		$this->template_service = $template_service;
	}

	public function get_core_message( $issues ) {
		if ( $issues ) {
			$strings = array(
				'title'   => __( 'Your WPML installation may cause problems with Block editor', 'sitepress' ),
				'message' => __( 'You are using WPML Translation Management without String Translation. Some of the translations may not work this way. Please download and install WPML String Translation before you translate the content from Block editor.', 'sitepress' ),
			);

			return $this->get_shared_message( $strings, $issues );
		}

		return null;
	}

	public function get_message( $issues, $limit = 0 ) {
		if ( $issues ) {
			$product_name = $this->get_product_names( $issues );

			if ( 'Elementor' === $product_name ) {
				$requirements = $issues['requirements'];

				if ( 1 === count( $requirements ) && 'WPML String Translation' === $requirements[0]['name'] ) {
					if ( Option::isTMAllowed() ) {
						$strings = $this->get_elementor_message_for_regular_account( $product_name );
					} else {
						$strings = $this->get_elementor_message_for_blog_account( $product_name );
					}

					$issues = [];
				} else {
					// When the product_name is Elementor but there is more than one required plugin or the required plugin is not string translation.
					$strings = $this->get_default_message( $issues );
				}
			} else {
				// When the product_name is not Elementor(default case).
				$strings = $this->get_default_message( $issues );
			}

			return $this->get_shared_message( $strings, $issues, $limit );
		}

		return null;
	}

	private function get_default_message( $issues ) {
		return [
			/* translators: %s is the product name, */
			'title' => sprintf( __( 'To easily translate %s, you need to add the following WPML components:', 'sitepress' ), $this->get_product_names( $issues ) ),
		];
	}

	private function get_elementor_message_for_blog_account( $product_name ) {
		$message_text_first_part_url = 'https://wpml.org/documentation/plugins-compatibility/elementor/translate-elementor-site-wpml-multilingual-blog/?utm_source=plugin&utm_medium=gui&utm_campaign=elementor';
		$message_text_last_part_url  = 'https://wpml.org/account/?utm_source=plugin&utm_medium=gui&utm_campaign=elementor';

		return [
			'title'   => __( 'Your WPML Blog account only allows you to manually translate Elementor content', 'sitepress' ),
			'message' => sprintf(
			/* translators: %1$s, %3$s, %6$s and %7$s are opening and closing link tags, %2$s and %5$s are the product name, %3$s are break tags. */
				esc_html__( '%1$sLearn how to translate %2$s content manually%3$s %4$sAlternatively, to translate %5$s content using the Advanced Translation Editor, automatic translation, professional services, or by other users on your site %6$supgrade to WPML CMS account%7$s.', 'sitepress' ),
				'<a href="' . $message_text_first_part_url . '" target="_blank">',
				$product_name,
				'</a>',
				'<br><br>',
				$product_name,
				'<a href="' . $message_text_last_part_url . '" target="_blank">',
				'</a>'
			),
		];
	}

	private function get_elementor_message_for_regular_account( $product_name ) {
		$title_link_url = admin_url( 'plugin-install.php?tab=commercial' );
		$message_url    = 'https://wpml.org/documentation/plugins-compatibility/elementor/?utm_source=plugin&utm_medium=gui&utm_campaign=elementor';

		return [
			'title'   => sprintf(
			/* translators: %1$s is the product name, %2$s and %3$s are opening and closing link tag. */
				esc_html__( 'To translate content created with %1$s, you need to %2$sinstall WPML String Translation%3$s', 'sitepress' ),
				$product_name,
				'<a href="' . $title_link_url . '" target="_blank">',
				'</a>'
			),
			'message' => sprintf(
			/* translators: %1$s and %3$s are opening and closing link tags, %2$s is the product name. */
				esc_html__( '%1$sLearn how to translate %2$s content%3$s', 'sitepress' ),
				'<a href="' . $message_url . '" target="_blank">',
				$product_name,
				'</a>'
			),
		];
	}

	private function get_shared_message( $strings, $issues, $limit = 0 ) {
		$strings = array_merge(
			array(
				'download'   => __( 'Download', 'sitepress' ),
				'install'    => __( 'Install', 'sitepress' ),
				'activate'   => __( 'Activate', 'sitepress' ),
				'activating' => __( 'Activating...', 'sitepress' ),
				'activated'  => __( 'Activated', 'sitepress' ),
				'error'      => __( 'Error', 'sitepress' ),
			),
			$strings
		);

		$model = array(
			'strings' => $strings,
			'shared'  => array(
				'install_link' => get_admin_url( null, 'plugin-install.php?tab=commercial' ),
			),
			'options' => array(
				'limit' => $limit,
			),
			'data'    => $issues,
		);

		return $this->template_service->show( $model, 'plugins-status.twig' );
	}

	public function get_settings( $integrations ) {

		if ( $integrations ) {
			$model = array(
				'strings' => array(
					/* translators: %s will be replaced with a list of plugins or themes. */
					'title'        => sprintf( __( 'One more step before you can translate on %s', 'sitepress' ), $this->build_items_in_sentence( $integrations ) ),
					'message'      => __( "You need to enable WPML's Translation Editor, to translate conveniently.", 'sitepress' ),
					'enable_done'  => __( 'Done.', 'sitepress' ),
					'enable_error' => __( 'Something went wrong. Please try again or contact the support.', 'sitepress' ),
				),
				'nonces'  => array(
					'enable' => wp_create_nonce( 'wpml_set_translation_editor' ),
				),
			);

			return $this->template_service->show( $model, 'integrations-tm-settings.twig' );
		}

		return null;
	}

	/**
	 * @param array $issues
	 *
	 * @return string
	 */
	private function get_product_names( $issues ) {
		$products = wp_list_pluck( $issues['causes'], 'name' );

		return $this->build_items_in_sentence( $products );
	}

	/**
	 * @param array<string> $items
	 *
	 * @return string
	 */
	private function build_items_in_sentence( $items ) {
		if ( count( $items ) <= 2 ) {
			/* translators: Used between elements of a two elements list */
			$product_names = implode( ' ' . _x( 'and', 'Used between elements of a two elements list', 'sitepress' ) . ' ', $items );

			return $product_names;
		}

		$last  = array_slice( $items, - 1 );
		$first = implode( ', ', array_slice( $items, 0, - 1 ) );
		$both  = array_filter( array_merge( array( $first ), $last ), 'strlen' );
		/* translators: Used before the last element of a three or more elements list */
		$product_names = implode( _x( ', and', 'Used before the last element of a three or more elements list', 'sitepress' ) . ' ', $both );

		return $product_names;
	}
}
