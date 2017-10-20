<?php

class WPML_End_User_JS_Loader implements IWPML_Action {

	const RESOURCE_NAMESPACE = 'wpml-end-user';

	/** @var  WPML_End_User_Notice_Validate */
	private $validator;

	/** @var  WPML_End_User_Page_Identify */
	private $page_identify;

	/**
	 * @param WPML_End_User_Notice_Validate $validator
	 * @param WPML_End_User_Page_Identify $page_identify
	 */
	public function __construct(
		WPML_End_User_Notice_Validate $validator,
		WPML_End_User_Page_Identify $page_identify
	) {
		$this->validator = $validator;
		$this->page_identify = $page_identify;
	}


	public function add_hooks() {
		if ( $this->validator->is_valid( get_current_user_id() ) || $this->load_how_to_button() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );
		}
	}

	public function enqueue_scripts() {
		wp_register_script( self::RESOURCE_NAMESPACE, ICL_PLUGIN_URL . '/res/js/end-user.js', array( 'jquery-ui-dialog' ) );
		wp_localize_script( self::RESOURCE_NAMESPACE, 'wpml_end_user_data', $this->get_how_to_link() );
		wp_enqueue_script( self::RESOURCE_NAMESPACE );
	}

	private function get_how_to_link() {
		$button = '
			<a id="icl_how_to_translate_link" 
			    class="otgs-ico-wpml wpml-external-link js-wpml-end-user-send-request"
			    target="_blank"
			    title="%1$s">
			 
			  %1$s
			
			</a>
		';

		$button = sprintf( $button, esc_html__( 'How to translate', 'sitepress' ) );

		return array(
			'button' => $button,
			'endpoint' => WPML_COMPATIBILITY_ENDPOINT,
			'confirm_button_label' => esc_attr__( 'Continue', 'sitepress' ),
		);
	}

	/**
	 * @return bool
	 */
	private function load_how_to_button() {
		return $this->page_identify->is_page_list() || $this->page_identify->is_tm_dashboard();
	}
}
