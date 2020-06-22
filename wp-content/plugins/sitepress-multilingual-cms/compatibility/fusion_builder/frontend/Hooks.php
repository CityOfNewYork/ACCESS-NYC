<?php

namespace WPML\Compatibility\FusionBuilder\Frontend;

use WPML\Compatibility\FusionBuilder\BaseHooks;

class Hooks extends BaseHooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \SitePress */
	private $sitepress;

	/** @var \WPML_Translation_Element_Factory */
	private $elementFactory;

	public function __construct(
		\SitePress $sitepress,
		\WPML_Translation_Element_Factory $elementFactory
	) {
		$this->sitepress      = $sitepress;
		$this->elementFactory = $elementFactory;
	}

	public function add_hooks() {
		if ( $this->isFusionBuilderRequest() ) {
			// Add action at high priority, as Avada cleans all scripts at priority 100.
			add_action( 'wp_enqueue_scripts', [ $this, 'frontendScripts' ], PHP_INT_MAX );
		}
	}

	public function frontendScripts() {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$post_element = $this->elementFactory->create( $post_id, 'post' );
		$is_original  = ! $post_element->get_source_language_code();

		if (
			$is_original
			|| ! (
				class_exists( '\WPML_TM_Post_Edit_TM_Editor_Mode' )
				&& \WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post_id )
			)
		) {
			return;
		}

		$message = sprintf(
			// translators: 1 and 2 are html <strong></strong> tags.
			__( '%1$sWarning:%2$s You are trying to add a translation using the Fusion Builder Live editor but your site is configured to use the WPML Translation Editor.', 'sitepress' ),
			'<strong>',
			'</strong>'
		);
		$message = '<p>' . $message . '</p>';
		$button  = '<button>' . __( 'OK', 'sitepress' ) . '</button>';
		$warning = '<div class="wpml-fusion-builder-live-edit-warning"><div class="wpml-message">' . $message . $button . '</div></div>';

		$this->enqueue_style();
		$this->enqueue_script();
		$this->localize_script( [ 'warning' => $warning ] );
	}

	private function isFusionBuilderRequest() {
		$builder    = filter_input( INPUT_GET, 'builder', FILTER_VALIDATE_BOOLEAN );
		$builder_id = filter_input( INPUT_GET, 'builder_id', FILTER_SANITIZE_STRING );

		return $builder && $builder_id;
	}

}
