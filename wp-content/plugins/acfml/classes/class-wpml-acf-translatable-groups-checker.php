<?php
/**
 * @author OnTheGo Systems
 */
class WPML_ACF_Translatable_Groups_Checker {
	const TRANSIENT_KEY = 'acfml_untranslated_groups';
	const POST_TYPE     = 'acf-field-group';

	/**
	 * @var WP_Post[]|void
	 */
	private $untranslated_groups;

	public function register_hooks() {
		if ( is_admin() && $this->is_field_groups_translatable() ) {
			add_action( 'admin_init', [ $this, 'check_untranslated_groups' ] );
		}
		add_action( 'save_post', [ $this, 'on_save_field_group' ], 10, 2 );
		add_action( 'icl_save_settings', [ $this, 'on_save_settings' ] );
	}

	public function check_untranslated_groups() {
		$this->untranslated_groups = get_transient( self::TRANSIENT_KEY );
		if ( false === $this->untranslated_groups ) {
			$this->untranslated_groups = $this->get_untranslated_field_groups();
			set_transient( self::TRANSIENT_KEY, $this->untranslated_groups );
		}

		$this->untranslated_groups = is_array( $this->untranslated_groups ) ? $this->untranslated_groups : [];
		if ( count( $this->untranslated_groups ) > 0 ) {
			add_action( 'admin_notices', [ $this, 'report_untranslated_groups' ] );
		}
	}

	public function report_untranslated_groups() {
		?>
		<div class="notice notice-error is-dismissible">
			<h2><?php esc_html_e( 'Some field groups are not translated.', 'acfml' ); ?></h2>
			<p><?php esc_html_e( 'Field Groups are translatable, this will let you translate the ACF interface. If that\'s not a requirement for your website, consider setting the fields group to not Translatable. However some groups do not have a translation yet. This may create inconsistencies.', 'acfml' ); ?>
				<?php esc_html_e( 'Read more about it on this ', 'acfml' ); ?>
				<a href="https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/#translating-field-groups"><?php esc_html_e( 'documentation article', 'acfml' ); ?></a>
			</p>
			<ul>
				<?php foreach ( $this->untranslated_groups as $group ) { ?>
					<li><a href="<?php echo esc_url( get_edit_post_link( $group ) ); ?>"><?php echo esc_html( $group->post_title ); ?></a></li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param int     $post_id the ID of the post.
	 * @param WP_Post $post    the post object.
	 * @return void
	 */
	public function on_save_field_group( $post_id, $post ) {
		if ( self::POST_TYPE === $post->post_type ) {
			delete_transient( self::TRANSIENT_KEY );
		}
	}

	/**
	 * @return void
	 */
	public function on_save_settings() {
		/* phpcs:disable WordPress.Security.NonceVerification.Missing */
		if ( \WPML\FP\Relation::propEq( 'icl_ajx_action', 'icl_custom_posts_sync_options', $_POST ) ) {
			delete_transient( self::TRANSIENT_KEY );
		}
	}

	/**
	 * @return bool
	 */
	private function is_field_groups_translatable() {
		return (bool) apply_filters( 'wpml_sub_setting', false, 'custom_posts_sync_option', self::POST_TYPE );
	}

	/**
	 * @return array
	 */
	private function get_untranslated_field_groups() {
		$groups = get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			]
		);

		$untranslated = [];
		foreach ( $groups as $group ) {
			if ( ! apply_filters( 'wpml_element_has_translations', null, $group->ID, self::POST_TYPE ) ) {
				$untranslated[] = $group;
			}
		}
		return $untranslated;
	}
}
