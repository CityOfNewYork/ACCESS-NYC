<?php

/**
 * Class WPML_User_Options_Menu
 * Renders the WPML UI elements on the WordPress user profile edit screen
 */
class WPML_User_Options_Menu {

	/** @var WP_User */
	private $current_user;
	/** @var SitePress */
	private $sitepress;
	/**
	 * @var string
	 */
	private $user_language;
	/**
	 * @var string
	 */
	private $user_admin_def_lang;
	/**
	 * @var string
	 */
	private $admin_default_language;
	/**
	 * @var string
	 */
	private $admin_language;
	/**
	 * @var mixed[]
	 */
	private $all_languages;

	/**
	 * WPML_User_Options_Menu constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WP_User   $current_user
	 */
	public function __construct( SitePress $sitepress, WP_User $current_user ) {
		$this->sitepress              = $sitepress;
		$this->current_user           = $current_user;
		$this->user_language          = $this->sitepress->get_wp_api()->get_user_meta( $this->current_user->ID, 'icl_admin_language', true );
		$this->user_admin_def_lang    = $this->sitepress->get_setting( 'admin_default_language' );
		$this->user_admin_def_lang    = $this->user_admin_def_lang === '_default_' ? $this->sitepress->get_default_language() : $this->user_admin_def_lang;
		$lang                         = $this->sitepress->get_language_details( $this->user_admin_def_lang );
		$this->admin_default_language = is_array( $lang ) && isset( $lang['display_name'] )
			? $lang['display_name']
			: $this->user_admin_def_lang;

		$this->admin_language         = $this->sitepress->get_admin_language();

		$user_language_for_all_languages = $this->user_admin_def_lang;
		if ( $this->user_language ) {
			$user_language_for_all_languages = $this->user_language;
		}
		$this->all_languages = $this->sitepress->get_languages( $user_language_for_all_languages );
	}

	/**
	 * @return string the html for the user profile edit screen element WPML
	 * adds to it
	 */
	public function render() {
		$wp_api              = $this->sitepress->get_wp_api();
		$hide_wpml_languages = (bool) $wp_api->version_compare_naked( get_bloginfo( 'version' ), '4.7', '>=' );
		ob_start();

		if ( ! $hide_wpml_languages ) {
			$admin_default_language_selected = $this->user_language === $this->user_admin_def_lang;
			?>
			<tr class="user-language-wrap">
				<th colspan="2"><h3><?php esc_html_e( 'WPML admin language', 'sitepress' ); ?></h3></th>
			</tr>
			<tr class="user-language-wrap">
				<th><label for="icl_user_admin_language"><?php esc_html_e( 'Select your language:', 'sitepress' ); ?></label></th>
				<td>
					<select id="icl_user_admin_language" name="icl_user_admin_language">
						<option value=""<?php selected( true, $admin_default_language_selected ); ?>>
							<?php
							/* translators: Current default admin language */
							echo esc_html( sprintf( __( 'Default admin language (currently %s)', 'sitepress' ), $this->admin_default_language ) );
							?>
						</option>
						<?php
						foreach ( array( true, false ) as $active ) {
							foreach ( (array) $this->all_languages as $lang_code => $al ) {
								if ( (bool) $al['active'] === $active ) {
									$current_language_selected = $this->user_language === $lang_code;

									$language_name = $al['display_name'];
									if ( $this->admin_language !== $lang_code ) {
										$language_name .= ' (' . $al['native_name'] . ')';
									}
									?>
									<option value="<?php echo esc_attr( $lang_code ); ?>"<?php selected( true, $current_language_selected ); ?>>
										<?php echo esc_html( $language_name ); ?>
									</option>
									<?php
								}
							}
						}
						?>
					</select>
					<span class="description">
					<?php esc_html_e( 'this will be your admin language and will also be used for translating comments.', 'sitepress' ); ?>
				</span>
				</td>
			</tr>
			<?php
		}

		$use_admin_language_for_edit = $wp_api->get_user_meta( $this->current_user->ID, 'icl_admin_language_for_edit', true );
		$this->get_hidden_languages_options( $use_admin_language_for_edit );

		do_action( 'wpml_user_profile_options', $this->current_user->ID );

		return ob_get_clean();
	}

	/**
	 * @param bool $use_admin_language_for_edit
	 */
	private function get_hidden_languages_options( $use_admin_language_for_edit ) {

		/**
		 * Filters a condition if current user can see hidden languages options in profile settings
		 *
		 * @params bool $show_hidden_languages_options
		 */
		$show_hidden_languages_options = apply_filters(
			'wpml_show_hidden_languages_options',
			current_user_can( 'manage_options' )
		);

		if ( $show_hidden_languages_options ) {
			$hidden_languages         = $this->sitepress->get_setting( 'hidden_languages' );
			$display_hidden_languages = get_user_meta( $this->current_user->ID, 'icl_show_hidden_languages', true );
			?>
			<tr class="user-language-wrap">
				<th colspan="2"><h3><?php esc_html_e( 'WPML language settings', 'sitepress' ); ?></h3></th>
			</tr>
			<tr class="user-language-wrap">
				<th><?php esc_html_e( 'Editing language:', 'sitepress' ); ?></th>
				<td>
					<input type="checkbox" name="icl_admin_language_for_edit" id="icl_admin_language_for_edit" value="1" <?php checked( true, $use_admin_language_for_edit ); ?> />
					<input id="icl_field_admin_language_for_edit" name="icl_field_admin_language_for_edit" type="hidden" value="1">
					&nbsp;<label for="icl_admin_language_for_edit"><?php esc_html_e( 'Set admin language as editing language.', 'sitepress' ); ?></label>
				</td>
			</tr>

			<tr class="user-language-wrap">
				<th><?php esc_html_e( 'Hidden languages:', 'sitepress' ); ?></th>
				<td>
					<p>
						<?php
						if ( ! empty( $hidden_languages ) ) {
							if ( 1 === count( $hidden_languages ) ) {
								echo esc_html( sprintf( __( '%s is currently hidden to visitors.', 'sitepress' ), $this->all_languages[ end( $hidden_languages ) ]['display_name'] ) );
							} else {
								$hidden_languages_array = array();
								foreach ( (array) $hidden_languages as $l ) {
									$hidden_languages_array[] = $this->all_languages[ $l ]['display_name'];
								}
								$hidden_languages = implode( ', ', $hidden_languages_array );
								echo esc_html( sprintf( __( '%s are currently hidden to visitors.', 'sitepress' ), $hidden_languages ) );
							}
						} else {
							esc_html_e( 'All languages are currently displayed. Choose what to do when site languages are hidden.', 'sitepress' );
						}
						?>
					</p>
					<p>
						<input id="icl_show_hidden_languages" name="icl_show_hidden_languages" type="checkbox" value="1" <?php checked( true, $display_hidden_languages ); ?> />
						<input id="icl_field_hidden_languages" name="icl_field_hidden_languages" type="hidden" value="1">
						&nbsp;<label for="icl_show_hidden_languages"><?php esc_html_e( 'Display hidden languages', 'sitepress' ); ?></label>
					</p>
				</td>
			</tr>
			<?php
		}
	}
}
