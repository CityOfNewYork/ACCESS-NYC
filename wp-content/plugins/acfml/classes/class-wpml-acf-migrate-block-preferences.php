<?php

namespace ACFML;

class MigrateBlockPreferences implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	const OPTION_KEY = 'acfml_block_migration_result';
	const CHUNK_SIZE = 10;
	const MIGRATED_VALUE = 'done';

	/**
	 * @var \WPML_ACF_Field_Settings
	 */
	private $fieldSettings;

	/**
	 * Migrate_Block_Preferences constructor.
	 *
	 * @param \WPML_ACF_Field_Settings $fieldSettings
	 */
	public function __construct( \WPML_ACF_Field_Settings $fieldSettings ) {
		 $this->fieldSettings = $fieldSettings;
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'runMigration' ] );
		add_action( 'plugins_loaded', [ $this, 'showProgressInfo' ] );
	}

	/**
	 * Check if migration should run and run it.
	 *
	 * After migration it updates wp_option to indicate where it finished or if all field groups has been migrated, sets value 'done'.
	 */
	public function runMigration() {
		if ( $this->migrationShouldRun() ) {
			$offset = $this->getOffset();
			if ( $this->migrate( $offset ) ) {
				\update_option( self::OPTION_KEY, $offset + self::CHUNK_SIZE );
			} else {
				\update_option( self::OPTION_KEY, self::MIGRATED_VALUE );
			}
		}
	}

	/**
	 * Displays admin notification informing migration is performed in the background.
	 *
	 * @uses \ACFML\MigrateBlockPreferences::blocksBeingUpdated
	 */
	public function showProgressInfo() {
		if ( $this->migrationShouldRun() ) {
			add_action( 'admin_notices', array( $this, 'blocksBeingUpdated' ) );
		}
	}

	/**
	 * HTML of the admin notice.
	 *
	 * @see \ACFML\MigrateBlockPreferences::showProgressInfo
	 */
	public function blocksBeingUpdated() {
		?>
		<div class="notice notice-info">
			<p><?php _e(
				'ACFML is updating translation preferences for strings in Gutenberg Blocks. Keep using your site as usual. This notice will disappear when the process is done.',
				'acfml' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Migrates chunk of the field group posts.
	 *
	 * @param int $offset Offset where to start query for field group posts.
	 *
	 * @return bool True of there were field groups still to be migrated, false if no field groups found.
	 */
	private function migrate( $offset = 0 ) {
		$fieldGroups = $this->getOnlyBlockLocatedGroups( (array) $this->getAllFieldGroups( $offset ) );
		if ( count( $fieldGroups ) > 0 ) {
			foreach ( $fieldGroups as $group ) {
                $this->migrateChildren( $group->ID );
			}
			return true;
		} else {
			return false;
		}
	}

	/**
     * Migrate children of given post.
     *
	 * @param int $parentId Parent post ID
	 */
	private function migrateChildren( $parentId ) {
		foreach ( $this->getFieldsOfGroup( $parentId ) as $field ) {
			$fieldObject = acf_get_field( $field->post_name );
			if ( $this->fieldSettings->fieldPreferencesNotMigrated( $fieldObject ) ) {
				if ( $this->fieldSettings->field_should_be_set_to_copy_once( $fieldObject ) ) {
				    $this->setFieldTranslationPreference( $fieldObject, WPML_COPY_CUSTOM_FIELD );
					$this->migrateChildren( $fieldObject['ID'] );
				} else {
					$this->setFieldTranslationPreference( $fieldObject, WPML_TRANSLATE_CUSTOM_FIELD );
				}
			}
		}
	}

	/**
     * Updates field translation preference in WPML settings.
     *
	 * @param array $fieldObject ACF field in format returned by @see \acf_get_field.
	 * @param int   $preference  Preference bit to set.
	 */
	private function setFieldTranslationPreference( $fieldObject, $preference ) {
		$fieldObject['wpml_cf_preferences'] = $preference;
		$this->fieldSettings->update_field_settings( $fieldObject );
		$this->fieldSettings->update_field_group_post( $fieldObject['ID'], $preference );
	}

	/**
	 * Gets field group posts from wp_posts.
	 *
	 * @param int $offset Offset where to start query for field group posts.
	 *
	 * @return int[]|\WP_Post[]
	 */
	private function getAllFieldGroups( $offset ) {
		return get_posts( [
			'numberposts' => self::CHUNK_SIZE,
			'offset' => $offset,
			'post_type' => 'acf-field-group'
		] );
	}

	/**
	 * Filters field group to return only those which has set location rule to be used in Gutenberg Blocks.
	 *
	 * @param \WP_Post[] $fieldGroups
	 *
	 * @return \WP_Post[]
	 */
	private function getOnlyBlockLocatedGroups( array $fieldGroups ) {
		$blockLocatedGroups = [];
		foreach ( $fieldGroups as $fieldGroup ) {
			if ( isset( $fieldGroup->post_content ) ) {
				if ( $this->hasBlockInDisplayRules( maybe_unserialize( $fieldGroup->post_content ) ) ) {
					$blockLocatedGroups[] = $fieldGroup;
				}
			}
		}
		return $blockLocatedGroups;
	}

	/**
	 * Gets fields from wp_posts being child of given field group.
	 *
	 * @param int $parentId The ID of the parent field group.
	 *
	 * @return int[]|\WP_Post[]
	 */
	private function getFieldsOfGroup( $parentId ) {
		return get_posts( [
			'numberposts' => -1,
			'post_type' => 'acf-field',
			'post_parent' => $parentId
		] );
	}

	/**
	 * Checks in wp_options if migration is not done yet.
	 *
	 * @return bool
	 */
	private function migrationShouldRun() {
		return self::MIGRATED_VALUE !== \get_option( self::OPTION_KEY );
	}

	/**
	 * Returns offset (used in @return int
	 * @see \ACFML\MigrateBlockPreferences::migrate ) stored in wp_options between migration chunks.
	 *
	 */
	private function getOffset() {
		return (int) \get_option( self::OPTION_KEY );
	}

	/**
	 * Checks if field group has display rule param.
	 *
	 * @param mixed  $fieldGroup Field group.
	 *
	 * @return bool
	 */
	private function hasBlockInDisplayRules( $fieldGroup ) {
		if ( isset( $fieldGroup['location'] ) && is_array( $fieldGroup['location'] ) ) {
			foreach ( $fieldGroup['location'] as $group ) {
				if ( empty( $group ) || ! is_array( $group ) ) {
					continue;
				}
				foreach ( $group as $rule ) {
					if ( isset( $rule['param'] ) && $rule['param'] === 'block' ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
