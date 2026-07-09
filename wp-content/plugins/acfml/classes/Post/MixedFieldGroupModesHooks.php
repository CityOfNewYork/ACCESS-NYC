<?php

namespace ACFML\Post;

use ACFML\Notice\Links;
use WPML\API\Sanitize;
use WPML\LIB\WP\Hooks;
use ACFML\FieldGroup\Mode;

class MixedFieldGroupModesHooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( self::shouldDisplayNotice() ) {
			Hooks::onAction( 'admin_notices' )->then( [ $this, 'displayWarningClassic'] );
			Hooks::onAction( 'wp_print_scripts' )->then( [ $this, 'displayWarningOnGutenberg' ] );
		}
	}

	/**
	 * @return bool
	 */
	public static function shouldDisplayNotice() {
		if ( ! \WPML_ACF::is_acf_active() ) {
			return false;
		}

		global $pagenow;

		$isPostEditScreen = in_array( $pagenow, [ 'post.php', 'post-new.php' ], true );
		if ( ! $isPostEditScreen ) {
			return false;
		}

		$postId   = isset( $_GET['post'] ) ? (int) $_GET['post'] : null;
		$postType = isset( $_GET['post_type'] ) ? Sanitize::string( $_GET['post_type'] ) : 'post';
		$fieldGroups = self::getFieldGroups( $postId, $postType );

		return count( $fieldGroups ) > 1 && Mode::MIXED === Mode::getForFieldGroups( $fieldGroups );
	}

	/**
	 * @param int|null    $postId
	 * @param string|null $postType
	 *
	 * @return array
	 */
	private static function getFieldGroups( $postId, $postType ) {
		if ( $postId ) {
			return acf_get_field_groups( [ 'post_id' => $postId ] );
		} else {
			return acf_get_field_groups( [ 'post_type' => $postType ] );
		}
	}

	/**
	 * @return void
	 */
	public function displayWarningClassic() {
		if ( function_exists( 'wpml_get_admin_notices' ) ) {
			$message = sprintf(
				/* translators: %1$s: opening <a> tag, %2$s: closing </a> tag. */
				__( 'You need to %1$stranslate this post manually%2$s because the field groups attached to it use different translation options.', 'acfml' ),
				'<a href="'. self::getNoticeLink() . '">',
				'</a>'
			);

			$notices = wpml_get_admin_notices();
			$notice  = $notices->create_notice( 'acfml-post-edit-translation-notice', $message , 'acfml' );
			$notice->set_dismissible( true );
			$notice->set_css_class_types( [ 'notice-warning' ] );
			$notice->add_display_callback( [ self::class, 'shouldDisplayNotice' ] );
			$notices->add_notice( $notice );
		}
	}

	/**
	 * @return void
	 */
	public function displayWarningOnGutenberg() {
		?>
		<script type="text/javascript">
			(function() {
				wp.data.dispatch("core/notices").createNotice(
					'warning',
					'<?php _e( 'You need to translate this post manually because the field groups attached to it use different translation options.', 'acfml' ); ?>',
					{
						id: 'acfml_post_edit_translation_notice',
						isDismissible: true,
						actions: [
							{
								url: '<?php echo self::getNoticeLink() ?>',
								label: '<?php _e( 'Go to documentation', 'acfml' ); ?>'
							}
						]
					}
				);
			})();
		</script>
		<?php
	}

	/**
	 * @return string
	 */
	private static function getNoticeLink() {
		return Links::getDifferentTranslationEditorsDoc( [
			'utm_content'  => 'manual-translation'
		] );
	}
}
