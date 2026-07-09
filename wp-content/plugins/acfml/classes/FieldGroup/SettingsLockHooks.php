<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\FieldGroup;
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
		return (bool) $this->getGroupId( $cfSetting ) ?: $isDisabled;
	}

	/**
	 * @param bool                       $override
	 * @param \WPML_Custom_Field_Setting $cfSetting
	 *
	 * @return bool
	 */
	public function renderCustomFieldLock( $override, $cfSetting ) {
		$groupId = $this->getGroupId( $cfSetting );

		if ( $groupId ) {
			$fieldGroup = acf_get_field_group( $groupId );

			if ( false === $fieldGroup ) {
				return $override;
			}

			$groupTitle = Obj::propOr( $groupId, 'title', $fieldGroup );

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

	/**
	 * @param \WPML_Custom_Field_Setting $cfSetting
	 *
	 * @return int|null
	 */
	private function getGroupId( $cfSetting ) {
		$name = $cfSetting->get_index();

		if ( acf_is_local_field( $name ) ) {
			return FieldGroup::getId( (int) Obj::prop( 'parent', acf_get_local_field( $name ) ) );
		}

		return $this->fieldNamePatterns->findMatchingGroup( $name ) ?: null;
	}
}
