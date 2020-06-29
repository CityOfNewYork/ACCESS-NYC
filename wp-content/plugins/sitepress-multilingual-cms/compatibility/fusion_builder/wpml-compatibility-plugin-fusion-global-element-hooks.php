<?php

use WPML\Compatibility\FusionBuilder\BaseHooks;

class WPML_Compatibility_Plugin_Fusion_Global_Element_Hooks extends BaseHooks implements \IWPML_Action {

	const BEFORE_ADD_GLOBAL_ELEMENTS_PRIORITY = 5;

	const GLOBAL_SHORTCODE_START = '[fusion_global id="';

	const ACTION = 'wpml_compatibility_fusion_get_template_translation_icons';

	const LAYOUTS_SCREEN_ID = 'fusion-builder_page_fusion-layouts';

	const SECTIONS_SCREEN_ID = 'fusion-builder_page_fusion-layout-sections';

	/** @var IWPML_Current_Language */
	private $current_language;

	/** @var WPML_Translation_Element_Factory */
	private $element_factory;

	/** @var WPML_Custom_Columns */
	private $custom_columns;

	/** @var WPML_Post_Status_Display */
	private $postStatusDisplay;

	/** @var array */
	private $activeLanguages;

	public function __construct(
		IWPML_Current_Language $current_language,
		WPML_Translation_Element_Factory $element_factory,
		WPML_Custom_Columns $custom_columns,
		array $activeLanguages,
		WPML_Post_Status_Display $postStatusDisplay
	) {
		$this->current_language  = $current_language;
		$this->element_factory   = $element_factory;
		$this->custom_columns    = $custom_columns;
		$this->activeLanguages   = $activeLanguages;
		$this->postStatusDisplay = $postStatusDisplay;
	}

	public function add_hooks() {
		add_filter(
			'content_edit_pre',
			[ $this, 'translate_global_element_ids' ],
			self::BEFORE_ADD_GLOBAL_ELEMENTS_PRIORITY
		);

		add_filter( 'fusion_get_override', [ $this, 'fusion_get_override_filter' ] );

		if ( is_admin() ) {
			add_filter( 'manage_fusion_element_posts_columns', [ $this, 'add_language_column_header' ] );
			add_action( 'manage_fusion_element_custom_column', [ $this, 'add_language_column_content' ], 10, 2 );

			add_filter( 'manage_fusion_tb_section_posts_columns', [ $this, 'add_language_column_header' ] );
			add_action( 'manage_fusion_tb_section_custom_column', [ $this, 'add_language_column_content' ], 10, 2 );

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'wp_ajax_' . self::ACTION, [ $this, 'get_template_translation_icons' ] );
		}

		add_filter( 'wpml_ls_exclude_in_menu', [ $this, 'wpml_ls_exclude_in_menu_filter' ] );
	}

	/**
	 * @param bool $render
	 *
	 * @return bool
	 */
	public function wpml_ls_exclude_in_menu_filter( $render ) {
		// Nonce is checked by fusion builder plugin.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['action'] ) ? filter_var( wp_unslash( $_POST['action'] ), FILTER_SANITIZE_STRING ) : '';

		if ( wp_doing_ajax() && 'fusion_app_partial_refresh' === $action ) {
			return false;
		}

		return $render;
	}

	public function translate_global_element_ids( $content ) {
		$pattern = '/' . preg_quote( self::GLOBAL_SHORTCODE_START, '[' ) . '([\d]+)"\]/';

		return preg_replace_callback( $pattern, [ $this, 'replace_global_id' ], $content );
	}

	private function replace_global_id( array $matches ) {
		$global_id   = (int) $matches[1];
		$element     = $this->element_factory->create( $global_id, 'post' );
		$translation = $element->get_translation( $this->current_language->get_current_language() );

		if ( $translation ) {
			$global_id = $translation->get_element_id();
		}

		return self::GLOBAL_SHORTCODE_START . $global_id . '"]';
	}

	/**
	 * Filter overrides.
	 *
	 * @param WP_Post|stdClass|false $override  The override.
	 *
	 * @return WP_Post|stdClass|false
	 */
	public function fusion_get_override_filter( $override ) {
		if ( ! $override instanceof \WP_Post ) {
			return $override;
		}

		$id = apply_filters( 'wpml_object_id', $override->ID, $override->post_type, true );

		return get_post( $id );
	}

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function add_language_column_header( $columns ) {
		return $this->custom_columns->add_posts_management_column( $columns );
	}

	public function enqueue_scripts() {
		$current_screen    = get_current_screen();
		$current_screen_id = $current_screen ? $current_screen->id : null;

		if ( ! in_array( $current_screen_id, [ self::LAYOUTS_SCREEN_ID, self::SECTIONS_SCREEN_ID ], true ) ) {
			return;
		}

		$this->enqueue_style();

		if ( self::LAYOUTS_SCREEN_ID !== $current_screen_id ) {
			return;
		}

		$this->enqueue_script();
		$this->localize_script(
			[
				'url'    => admin_url( 'admin-ajax.php' ),
				'action' => self::ACTION,
				'nonce'  => wp_create_nonce( self::ACTION ),
			]
		);
	}

	/**
	 * @param string     $column_name
	 * @param array|null $item
	 */
	public function add_language_column_content( $column_name, $item = null ) {
		$id = $item ? $item['id'] : null;
		$this->custom_columns->add_content_for_posts_management_column( $column_name, $id );
	}

	public function get_template_translation_icons() {
		check_admin_referer( self::ACTION, 'nonce' );

		$ids = isset( $_POST['ids'] ) ? filter_var( wp_unslash( $_POST['ids'] ), FILTER_SANITIZE_STRING ) : '';
		$ids = empty( $ids ) ? [] : array_unique( array_map( 'intval', explode( ',', $ids ) ) );

		$icons = [];
		foreach ( $ids as $id ) {
			$icons[ $id ] = '';
			foreach ( $this->activeLanguages as $language_data ) {
				$icon_html = $this->postStatusDisplay->get_status_html( $id, $language_data['code'] );
				$icon_html = str_replace( 'class="js-wpml-translate-link"', 'class="control js-wpml-translate-link"', $icon_html );

				$icons[ $id ] .= $icon_html;
			}

			if ( ! empty( $icons[ $id ] ) ) {
				$icons[ $id ] = '<div class="wpml-template-translations">' . $icons[ $id ] . '</div>';
			}
		}

		$flags_column = $this->custom_columns->get_flags_column();
		$flags        = '';
		if ( ! empty( $icons ) && ! empty( $flags_column ) ) {
			$flags = '<div class="wpml-template-flags">' . $flags_column . '</div>';
		}

		wp_send_json_success(
			[
				'icons' => $icons,
				'flags' => $flags,
			]
		);
	}
}
