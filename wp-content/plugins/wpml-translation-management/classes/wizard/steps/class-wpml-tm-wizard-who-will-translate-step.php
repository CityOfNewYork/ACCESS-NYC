<?php

class WPML_TM_Wizard_Who_Will_Translate_Step extends WPML_Twig_Template_Loader {

	private $model = array();

	/** @var WP_User $user */
	private $user;

	/** @var WPML_Translation_Manager_Settings $translation_manager_settings */
	private $translation_manager_settings;

	/** @var WPML_TM_Wizard_Translators_Step $translator_settings */
	private $translator_settings;

	/** @var WPML_TM_Translation_Services_Admin_Section_Factory $translation_services_factory */
	private $translation_services_factory;

	/** @var array $who_will_translate_mode */
	private $who_will_translate_mode;

	public function __construct(
		WP_User $user,
		WPML_Translation_Manager_Settings $translation_manager_settings,
		WPML_Translator_Settings $translator_settings,
		WPML_TM_Translation_Services_Admin_Section_Factory $translation_services_factory,
		$who_will_translate_mode
	) {
		parent::__construct( array(
				WPML_TM_PATH . '/templates/wizard',
				WPML_PLUGIN_PATH . '/templates/widgets'
			)
		);
		$this->user                         = $user;
		$this->translation_manager_settings = $translation_manager_settings;
		$this->translator_settings          = $translator_settings;
		$this->translation_services_factory = $translation_services_factory;
		$this->who_will_translate_mode      = is_array( $who_will_translate_mode ) ? $who_will_translate_mode : array();
	}

	public function render() {
		global $wpdb;

		$this->add_strings();
		$this->add_translation_manager_ui();
		$this->add_translators_ui();
		$this->add_translation_services_ui();
		$this->add_mode();
		$this->add_nonce();
		$this->add_user_capability();

		return $this->get_template()->show( $this->model, 'who-will-translate-step.twig' );
	}

	public function add_strings() {


		$this->model['strings'] =
			array(
				/* translators: This is the title of the first screen of Translation Management wizard */
				'title'                      => __( 'Who will translate this site?', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Move to the next step of the wizard */
				'button_text'                => __( 'Continue', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - When asked who will translate this site, the user (%s) can chose to be the only translator */
				'only_i'                     => sprintf( __( 'Only myself (%s)', 'wpml-translation-management' ), $this->user->display_name ),
				/* translators: Translation Management wizard - When asked who will translate this site, the user can select or create a user to manage the translators */
				'leave_choice'               => __( 'I want to set a "Translation Manager" who will choose the translators for this site', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - When asked who will translate this site, the user can select or create users as translators */
				'users'                      => __( 'Users of this site', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - When asked who will translate this site,  */
				'translation_service'        => __( 'A Translation Service', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - tooltip text shown near the "Only myself (%s)" option  */
				'only_i_help'                => __( 'You will be the sole translator for this site. You will be able to translate any document between any language pair. If you want to do some of the translations and allow others to translate as well, select the "Users of this site" option instead.', 'wpml-translation-management' ),
				'leave_choice_help'          => array(
					/* translators: Translation Management wizard - tooltip text shown near the "I want to set a "Translation Manager" who ..." option  */
					'text' => __( 'You will choose a user of this site and delegate the setup of translators to him/her. Choose this option when you are building a website for a client, who will set up translator users or choose a translation service.', 'wpml-translation-management' ),
					'link' => array(
						/* translators: Translation Management wizard - the tooltip text shown near the "I want to set a "Translation Manager" who ..." option, will also provide a link to an external 'Working with Translation Managers' page (%s) */
						'pattern' => __( 'Read more on %s', 'wpml-translation-management' ),
						/* translators: Translation Management wizard - the tooltip text shown near the "I want to set a "Translation Manager" who ..." option, will also provide a link to an external 'Working with Translation Managers' page */
						'text'    => __( 'Working with Translation Managers', 'wpml-translation-management' ),
						'url'     => 'https://wpml.org/documentation/translating-your-contents/working-with-translation-managers/?utm_source=wpmlplugin&utm_campaign=tm-setup-wizard&utm_medium=translation-manager-tooltip&utm_term=translation-management',
					),
				),
				/* translators: Translation Management wizard - tooltip text shown near the "Users of this site" option */
				'users_help'                 => __( 'You will choose users of this WordPress site as the translators. You can also choose yourself. This allows you to set up a team of translators working in different language pairs.', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - tooltip text shown near the "A Translation Service" option  */
				'translation_service_help'   => __( 'WPML offers tight integration with over 70 translation services. You will choose the translation service that you prefer and enjoy a streamlined process for sending jobs and receiving completed translations.', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - when selecting the "A Translation Service" option, a button appears which allows selecting a translation service */
				'choose_translation_service' => __( 'Choose a Translation Service', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - a generic "Cancel" button shown when opening a dialog */
				'dialog_cancel'              => __( 'Cancel', 'wpml-translation-management' ),
			);

		$this->model['strings']['translation_service_dialog'] =
			array(
				/* translators: Translation Management wizard - The dialog title for selecting a translation service */
				'title' => __( 'Choose a Translation Service', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - A short explanation of the dialog's purpose */
				'into'  => __( 'Here is the full list of translation services that are integrated with WPML. With any service that you choose, you enjoy a streamlined process.', 'wpml-translation-management' ),
			);

		$this->model['strings']['activate_translation_service_dialog'] =
			array(
				/* translators: Translation Management wizard - Shown after selecting a translation service */
				'title'               => __( 'Connect this site to your %s account', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. "%s" is replaced with the name of the selected translation service */
				'connect_desc'        => __( 'Inside your %s account, you will find an "API token". This token allows WPML to connect to your account at %s to send and receive jobs.', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. "%s" is replaced with the name of the selected translation service */
				'connect_how_to_find' => __( 'How to find API token in %s', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. */
				'cancel'              => __( 'Use a different Translation Service', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. */
				'ok'                  => __( 'Authenticate', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. "%s" is replaced with the name of the selected translation service */
				'no_account'          => __( 'No account at %s?', 'wpml-translation-management' ),
				/* translators: Translation Management wizard - Shown after selecting a translation service. */
				'create_one'          => __( 'Create an account in one minute', 'wpml-translation-management' ),
			);

	}


	public function add_translation_manager_ui() {
		$this->model['translation_manager_ui'] = $this->translation_manager_settings->render();
	}

	public function add_translators_ui() {
		$this->model['translators_ui'] = $this->translator_settings->render( true );
	}

	private function add_translation_services_ui() {
		$this->handle_translation_service_params();

		$renderer = $this->translation_services_factory->create();

		ob_start();
		$renderer->render();
		$this->model['translation_services_table'] = ob_get_clean();
	}

	private function add_mode() {
		$this->model['mode'] = wp_json_encode( $this->who_will_translate_mode );
	}

	private function add_nonce() {
		$this->model['nonce'] = wp_create_nonce( WPML_TM_Wizard_Steps::NONCE );
	}

	private function add_user_capability() {
		$this->model['is_administrator'] = $this->user->has_cap( 'manage_options');
	}

	private function handle_translation_service_params() {

		$query_args = array();
		foreach ( array( 'orderby', 'order', 'paged' ) as $param ) {
			if ( isset( $_POST[ $param ] ) ) {
				$query_args[ $param ] = $_POST[ $param ];
				$_GET[ $param ]       = $_POST[ $param ];
			}
		}
		if ( ! empty( $query_args ) ) {
			$_SERVER['REQUEST_URI'] = add_query_arg( $query_args, $_SERVER['REQUEST_URI'] );
		}
	}

}
