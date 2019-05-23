<?php

class WPML_TM_Wizard_Summary_Step extends WPML_Twig_Template_Loader {

	private $model = array();

	/** @var WPML_Translator_Records $translator_records */
	private $translator_records;

	/** @var WPML_TP_Service $active_translation_service */
	private $active_translation_service;

	/** @var array $who_will_translate_mode */
	private $who_will_translate_mode;

	public function __construct(
		WPML_Translator_Records $translator_records,
		$who_will_translate_mode,
		WPML_TP_Service $active_translation_service = null
	) {
		parent::__construct( array(
				WPML_TM_PATH . '/templates/wizard',
			)
		);
		$this->translator_records         = $translator_records;
		$this->active_translation_service = $active_translation_service;
		$this->who_will_translate_mode    = is_array( $who_will_translate_mode ) ? $who_will_translate_mode : array();
	}

	public function render() {
		$this->add_strings();
		$this->add_translators();
		$this->add_translation_service();
		$this->add_mode();

		return $this->get_template()->show( $this->model, 'summary-step.twig' );
	}

	public function add_strings() {

		$this->model['strings'] = array(
			'title'               => __( 'Summary', 'wpml-translation-management' ),
			'translation_service' => __( 'Your translation service', 'wpml-translation-management' ),
			'local_translators'   => __( 'Your translators', 'wpml-translation-management' ),
			'local_summary'       => __( 'WPML created the accounts for your translators and sent them instructions.', 'wpml-translation-management' ),
			'instructions'        => $this->get_instructions(),
			'go_back'             => __( "Go back", 'wpml-translation-management' ),
			'done'                => __( "Done!", 'wpml-translation-management' ),
		);
	}

	public function add_translators() {
		$this->model['translators'] = $this->translator_records->get_users_with_capability();
	}

	public function add_translation_service() {
		$this->model['translation_service'] = $this->active_translation_service;
	}

	private function add_mode() {
		$this->model['mode']   = wp_json_encode( $this->who_will_translate_mode );
		$this->model['only_i'] = $this->is_only_i();
	}

	private function is_only_i() {
		return isset( $this->who_will_translate_mode['onlyI'] )
			? 'true' === $this->who_will_translate_mode['onlyI'] : false;
	}

	private function has_translators() {
		return isset( $this->who_will_translate_mode['user'] )
			? 'true' === $this->who_will_translate_mode['user'] : false;
	}

	private function has_translation_service() {
		return isset( $this->who_will_translate_mode['translationService'] )
			? 'true' === $this->who_will_translate_mode['translationService'] : false;
	}

	/** @return string */
	private function get_instructions() {
		$capability = 'translation_manager';

		$text = '';

		if ( $this->is_only_i() ) {
			$capability = 'only_i';
		} elseif ( $this->has_translators() && ! $this->has_translation_service() ) {
			$capability = 'users_of_this_site_only';
		} elseif ( $this->has_translation_service() && ! $this->has_translators() ) {
			$capability = 'ts_only';
		} elseif ( $this->has_translators() && $this->has_translation_service() ) {
			$capability = 'users_and_ts';
		}

		$text .= $this->get_top_instruction( $capability );
		$text .= $this->get_instruction_items( $capability );
		$text .= $this->get_bottom_instruction( $capability );

		return $text;
	}

	private function get_placeholder_value( $key ) {
		$strings_definitions = array(

			'plus' => array(
				'open'  => '<strong>',
				/* translators: The word "plus" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'plus', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'pencil' => array(
				'open'  => '<strong>',
				/* translators: The word "pencil" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'pencil', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'language' => array(
				'open'  => '<strong>',
				/* translators: The word "Language" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'Language', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'getting_started' => array(
				'open'  => '<a href="https://wpml.org/documentation/getting-started-guide/#how-to-translate-different-kinds-of-content" target="_blank" rel="noopener">',
				/* translators: "Getting Started Guide" will be used in other strings and wrapped with HTML tags to make it a link to external documentation */
				'text'  => esc_html__( 'Getting Started Guide', 'wpml-translation-management' ),
				'close' => '</a>',
			),

			'wpml_tm_management' => array(
				'open'  => '<strong>',
				/* translators: "WPML > Translation Management" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'WPML > Translation Management', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'translators' => array(
				'open'  => '<strong>',
				/* translators: "Translators" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'Translators', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'translation_roles' => array(
				'open'  => '<strong>',
				/* translators: "Translation Roles" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'Translation Roles', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'wpml_tm_translators' => array(
				'open'  => '<strong>',
				/* translators: "WPML > Translations" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'WPML > Translations', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'translating_your_content' => array(
				'open'  => '<a href="https://wpml.org/documentation/translating-your-contents/" target="_blank" rel="noopener">',
				/* translators: "Translation Management" will be used in other strings and wrapped with HTML tags to make it a link to external documentation */
				'text'  => esc_html__( 'Translation Management', 'wpml-translation-management' ),
				'close' => '</a>',
			),

			'check_status' => array(
				'open'  => '<strong>',
				/* translators: "Check status and get translations" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'Check status and get translations', 'wpml-translation-management' ),
				'close' => '</strong>',
			),

			'professional_translation_via_wpml' => array(
				'open'  => '<a href="https://wpml.org/documentation/translating-your-contents/professional-translation-via-wpml/" target="_blank" rel="noopener">',
				/* translators: "successfully using translation services" will be used in other strings and wrapped with HTML tags to make it a link to external documentation */
				'text'  => esc_html__( 'successfully using translation services', 'wpml-translation-management' ),
				'close' => '</a>',
			),

			'translation_basket' => array(
				'open'  => '<strong>',
				/* translators: "Translation Basket" will be used in other strings and wrapped with HTML tags to make it more prominent */
				'text'  => esc_html__( 'Translation Basket', 'wpml-translation-management' ),
				'close' => '</strong>',
			),
		);

		if ( array_key_exists( $key, $strings_definitions ) ) {
			$string_data = $strings_definitions [ $key ];

			return $this->build_string( $string_data );
		}

		return '';
	}

	private function get_top_instruction( $capability ) {
		$strings_definitions = array(
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf(
					esc_html__( 'You will be the only translator for this site and will be able to translate any page or post. Use the %1$s or a %2$s icon found on the post listing pages in the admin or in the %3$s box when editing content.', 'wpml-translation-management' ),
					$this->get_placeholder_value( 'plus' ),
					$this->get_placeholder_value( 'pencil' ),
					$this->get_placeholder_value( 'language' )
				),
				'capabilities' => array( 'only_i' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => esc_html__( 'You selected users of this site to be your translators. You need to:', 'wpml-translation-management' ),
				'capabilities' => array( 'users_of_this_site_only' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => esc_html__( 'You selected a translation service to translate your content. You need to:', 'wpml-translation-management' ),
				'capabilities' => array( 'ts_only' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => esc_html__( 'You selected both users of this site and a professional translation service. You need to:', 'wpml-translation-management' ),
				'capabilities' => array( 'users_and_ts' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => esc_html__( "You're all set. The user that you've selected as the site's Translation Manager will choose who translates, add translator users and send content for translation.", 'wpml-translation-management' ),
				'capabilities' => array( 'translation_manager' ),
			),
		);

		$results = '';
		foreach ( $strings_definitions as $definition ) {
			$results .= $this->build_string( $definition, $capability );
		}

		return $results;

	}

	private function get_bottom_instruction( $capability ) {
		$strings_definitions = array(

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Read the %1$s to learn about translating images, menus, widgets and more.', 'wpml-translation-management' ), $this->get_placeholder_value( 'getting_started' ) ),
				'capabilities' => array( 'only_i', 'ts_only' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				// translators: %1$s and %2$s will be replaced with "Translation Management" and "Translation Roles" respectively.
				'text'         => sprintf( esc_html__( 'To add more users of this site as translators, go to the %1$s page and click the %2$s tab.', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_management' ), $this->get_placeholder_value( 'translation_roles' ) ),
				'capabilities' => array( 'only_i' ),
			),

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Visit our documentation to learn more about using %1$s to learn about translating images, menus, widgets and more.', 'wpml-translation-management' ), $this->get_placeholder_value( 'translating_your_content' ) ),
				'capabilities' => array( 'users_of_this_site_only', 'users_and_ts' ),
			),

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Visit our documentation to learn more about %1$s.', 'wpml-translation-management' ), $this->get_placeholder_value( 'professional_translation_via_wpml' ) ),
				'capabilities' => array( 'ts_only' ),
			),
		);

		$results = '';
		foreach ( $strings_definitions as $definition ) {
			$results .= $this->build_string( $definition, $capability );
		}

		return $results;

	}

	private function get_instruction_items( $capability ) {
		$items = array(

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Use the %1$s page to select content for translation and send it to your translators.', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_management' ) ),
				'capabilities' => array( 'users_of_this_site_only', 'users_and_ts' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Translators will receive notifications and use the %1$s page to translate the content that you\'ve sent them.', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_translators' ) ),
				'capabilities' => array( 'users_of_this_site_only' ),
			),

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Use the %1$s page to select content for translation and send it to the translation service.', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_management' ) ),
				'capabilities' => array( 'ts_only' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Once the translation jobs are completed, you can get them into your site by using the %1$s button on the %2$s page.', 'wpml-translation-management' ), $this->get_placeholder_value( 'check_status' ), $this->get_placeholder_value( 'wpml_tm_management' ) ),
				'capabilities' => array( 'ts_only' ),
			),

			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'In the %1$s page, choose who will translate.', 'wpml-translation-management' ), $this->get_placeholder_value( 'translation_basket' ) ),
				'capabilities' => array( 'users_and_ts' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'Your translators will receive notifications and use the %1$s page to translate the content that you\'ve sent them.', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_translators' ) ),
				'capabilities' => array( 'users_and_ts' ),
			),
			array(
				'open'         => '<p>',
				'close'        => '</p>',
				'text'         => sprintf( esc_html__( 'To collect completed translations from the translation service, go to %1$s and click on the %2$s button', 'wpml-translation-management' ), $this->get_placeholder_value( 'wpml_tm_management' ), $this->get_placeholder_value( 'check_status' ) ),
				'capabilities' => array( 'users_and_ts' ),
			),

		);

		$results = array();
		foreach ( $items as $item ) {
			$results[] = $this->build_string( $item, $capability );
		}
		$results = array_filter( $results );

		if (  $results ) {
			return '<ol><li>' . implode( '</li><li>', $results ) . '</li></ol>';
		}

		return '';
	}

	private function build_string( $string_data, $capability = null ) {
		$result = '';
		if ( ! $capability || ( array_key_exists( 'capabilities', $string_data ) && ( ! $string_data['capabilities'] || in_array( $capability, $string_data['capabilities'] ) ) ) ) {
			if ( array_key_exists( 'open', $string_data ) ) {
				$result .= $string_data['open'];
			}

			$result .= $string_data['text'];

			if ( array_key_exists( 'close', $string_data ) ) {
				$result .= $string_data['close'];
			}
		}

		return $result;
	}
}
