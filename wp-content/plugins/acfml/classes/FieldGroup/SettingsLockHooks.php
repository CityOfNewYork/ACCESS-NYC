<?php

namespace ACFML\FieldGroup;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class SettingsLockHooks implements \IWPML_Action {

	/**
	 * @var FieldNamePatterns $fieldNamePatterns
	 */
	private $fieldNamePatterns;

	public function __construct( FieldNamePatterns $fieldNamePatterns ) {
		$this->fieldNamePatterns = $fieldNamePatterns;
	}

	public function add_hooks() {
		Hooks::onFilter( 'wpml_custom_field_setting_is_html_disabled', 10, 2 )
			->then( spreadArgs( [ $this, 'disableCustomFieldPreference' ] ) );

		Hooks::onFilter( 'wpml_custom_field_settings_override_lock_render', 10, 2 )
			->then( spreadArgs( [ $this, 'renderCustomFieldLock' ] ) );

		Hooks::onAction( 'acf/delete_field_group' )
			->then( spreadArgs( [ $this, 'deleteFieldGroupLock' ] ) );
	}

	/**
	 * @param bool                       $isDisabled
	 * @param \WPML_Custom_Field_Setting $cfSetting
	 *
	 * @return bool
	 */
	public function disableCustomFieldPreference( $isDisabled, $cfSetting ) {
		$fieldName = $cfSetting->get_index();
		$groupKey  = $this->fieldNamePatterns->findMatchingGroup( $fieldName );

		if ( $groupKey ) {
			$fieldGroup = acf_get_field_group( $groupKey );
			if ( false === $fieldGroup ) {
				return $isDisabled;
			}
			return true;
		}

		if ( $this->fieldNamePatterns->findMatchingLocalGroup( $fieldName ) ) {
			return true;
		}

		return $isDisabled;
	}

	/**
	 * @param bool                       $override
	 * @param \WPML_Custom_Field_Setting $cfSetting
	 *
	 * @return bool
	 */
	public function renderCustomFieldLock( $override, $cfSetting ) {
		$fieldName = $cfSetting->get_index();
		$groupKey  = $this->fieldNamePatterns->findMatchingGroup( $fieldName );

		if ( $groupKey ) {
			$fieldGroup = acf_get_field_group( $groupKey );
			if ( false === $fieldGroup ) {
				return $override;
			}

			$groupId = Obj::prop( 'ID', $fieldGroup );
			if ( ! $groupId ) {
				return $override;
			}

			$groupTitle = Obj::propOr( $groupKey, 'title', $fieldGroup );

			?>
			<a href="<?php echo esc_url( acf_get_field_group_edit_link( $groupId ) ); ?>" style="text-decoration: none;">
				<button type="button"
						class="button-secondary wpml-button-lock"
						<?php /* translators: %s is the field group title. */ ?>
						title="<?php printf( esc_attr__( 'To change the translation options for custom fields, edit the field group "%s".', 'acfml' ), $groupTitle ); // phpcs:ignore ?>">
					<i class="otgs-ico-lock"></i>
				</button>
			</a>
			<?php

			return true;
		}

		if ( $this->fieldNamePatterns->findMatchingLocalGroup( $fieldName ) ) {
			?>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=acf-field-group&post_status=sync' ) ); ?>" style="text-decoration: none;">
				<button type="button"
						class="button-secondary wpml-button-lock"
						title="<?php esc_attr_e( 'These fields come from ACF’s Local JSON files. To change their translation options, go to ACF → Field Groups, sync them, and then edit their settings.', 'acfml' ); // phpcs:ignore ?>">
					<i class="otgs-ico-lock"></i>
				</button>
			</a>
			<?php

			return true;
		}

		if ( $this->fieldNamePatterns->findMatchingLocalGroup( $fieldName, 'php' ) ) {
			?>
			<span class="acfml-field-info">
				<i class="otgs-ico-info-o" title="<?php esc_attr_e( 'This field and its translation setting are registered via PHP by your theme or plugin. Changes made here will override the original configuration.', 'acfml' ); ?>"></i>
			</span>
			<?php

			return true;
		}

		return $override;
	}

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function deleteFieldGroupLock( $fieldGroup ) {
		$this->fieldNamePatterns->updateGroup( Obj::prop( 'ID', $fieldGroup ), [] );
	}

}
