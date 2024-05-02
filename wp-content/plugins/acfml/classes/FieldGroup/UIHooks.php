<?php

namespace ACFML\FieldGroup;

use ACFML\FieldGroup\Endpoints\DismissTranslateCptModal;
use ACFML\Helper\FieldGroup;
use ACFML\Helper\Resources;
use ACFML\Notice\Links;
use ACFML\Strings\Package;
use ACFML\Strings\TranslationJobHooks;
use ACFML\Upgrade\Commands\MigrateToV2;
use WPML\FP\Obj;
use WPML\FP\Wrapper;
use WPML\LIB\WP\Hooks;

class UIHooks implements \IWPML_Action {

	const SCREEN_SLUG = 'acf-field-group';

	public function add_hooks() {
		Hooks::onAction( 'admin_print_scripts' )->then( [ __CLASS__, 'addMetaBox' ] );
		Hooks::onAction( 'admin_enqueue_scripts' )->then( [ __CLASS__, 'enqueueAssets' ] );
	}

	public static function addMetaBox() {
		add_meta_box(
			'acfml-field-group-setup',
			'<i class="otgs-ico-translation"></i>&nbsp;' . esc_html__( 'Multilingual Setup', 'acfml' ),
			function() {
				echo '<div id="acfml-field-group-ml-setup">' . esc_html__( 'Loading...', 'acfml' ) . '</div>';
			},
			self::SCREEN_SLUG,
			'normal',
			'high'
		);
	}

	public static function enqueueAssets() {
		if ( FieldGroup::isScreen() ) {
			Wrapper::of( self::getData() )->map( Resources::enqueueApp( 'field-group-edit' ) );
		}
	}

	/**
	 * @return array
	 */
	private static function getData() {
		$fieldGroupId        = Obj::prop( 'ID', get_post() );
		$fieldGroup          = (array) acf_get_field_group( $fieldGroupId );
		$fieldGroupMode      = Mode::getMode( $fieldGroup );
		$isNewGroup          = self::isNewGroup( $fieldGroupId );
		$attachedPosts       = AttachedPosts::getCount( $fieldGroupId );
		$nonTranslatableType = DetectNonTranslatableLocations::getDetectedType( $fieldGroupId );

		return [
			'name' => 'acfmlFieldGroupEdit',
			'data' => [
				'endpoints'                          => [
					'dismissTranslateCptModal' => DismissTranslateCptModal::class,
				],
				'fieldGroupId'                       => $fieldGroupId,
				'pluginImageURI'                     => ACFML_PLUGIN_URL . '/assets/img/',
				'fieldGroupMode'                     => $fieldGroupMode,
				'STModalData'                        => self::getSTModal( $fieldGroupId, $fieldGroupMode ),
				'strings'                            => self::getStrings( $attachedPosts, $nonTranslatableType ),
				'hasAcfml1Tooltip'                   => $isNewGroup && MigrateToV2::needsNotification(),
				'hasTranslateCptModal'               => (bool) $nonTranslatableType,
				'translateCptUrl'                    => admin_url( 'admin.php?page=tm/menu/settings#ml-content-setup-sec-7' ),
				'needsTranslationStatusProcessModal' => (bool) $attachedPosts,
				'docLinks'                           => self::getDocLinks(),
			],
		];
	}

	/**
	 * @param int $groupId
	 *
	 * @return bool
	 */
	private static function isNewGroup( $groupId ) {
		return 'auto-draft' === get_post_status( $groupId );
	}

	/**
	 * @param int         $fieldGroupId
	 * @param null|string $fieldGroupMode
	 *
	 * @return array
	 */
	private static function getSTModal( $fieldGroupId, $fieldGroupMode ) {
		$status = Package::create( $fieldGroupId )->getStatus();

		switch ( $status ) {
			case Package::STATUS_ST_INACTIVE:
				return [
					'title'        => esc_html__( 'Activate String Translation', 'acfml' ),
					'content'      => sprintf(
						/* translators: %1$s and %2$s will wrap the string in a <a> link html tag */
						esc_html__( 'To translate field group names and labels, please %1$sinstall and activate WPML’s String Translation add-on%2$s.', 'acfml' ),
						'<a href="' . Links::getFaqInstallST() . '" class="wpml-external-link" target="_blank">',
						'</a>'
					),
					'okText'       => esc_html__( 'Activate now', 'acfml' ),
					'cancelText'   => esc_html__( 'Go back', 'acfml' ),
					'redirectOnOk' => admin_url( '/plugins.php' ),
				];

			case Package::STATUS_NOT_REGISTERED:
				return [
					'title'        => esc_html__( 'Choose a translation option for your field groups', 'acfml' ),
					'content'      => esc_html__( 'To translate field labels in String Translation, please complete the Multilingual Setup for your existing field groups and save your changes.', 'acfml' ),
					'okText'       => esc_html__( 'OK', 'acfml' ),
					'cancelText'   => esc_html__( 'Cancel', 'acfml' ),
					'redirectOnOk' => null,
				];

			case Package::STATUS_NOT_TRANSLATED:
				return [
					'title'        => esc_html__( 'Translating field labels', 'acfml' ),
					'content'      => [
						Mode::TRANSLATION  => '<p>' . esc_html__( 'The first time you translate a post that uses this field group, you can translate the field labels in the Advanced Translation Editor or using automatic translation.', 'acfml' ) . '</p>'
											  . '<p>' . esc_html__( 'Or, you can translate the field labels on the String Translation page anytime.', 'acfml' ) . '</p>'
											  . '<p>' . sprintf(
												  /* translators: %1$s and %2$s will wrap the string in a <a> link html tag */
							                      esc_html__( 'Don’t want to translate field labels? %1$sLearn how to disable field label translation%2$s', 'acfml' ),
							                      '<a href="' . Links::getAcfmlTranslateLabels( 'excluding-field-labels-from-the-advanced-translation-editor' ) . '" class="wpml-external-link" target="_blank">',
							                      '</a>'
						                      ) . '</p>',
						Mode::LOCALIZATION => '<p>' . esc_html__( 'You can translate field labels and labels for Choices in String Translation.', 'acfml' ) . '</p>',
						Mode::ADVANCED     => '<p>'
											  . sprintf(
												  /* translators: %1$s and %2$s will wrap the string in a <a> link html tag */
												  esc_html__( 'You can translate field labels in the Advanced Translation Editor or in String Translation, depending on the %1$stranslation preferences you select for your custom fields%2$s.', 'acfml' ),
												  '<a href="' . Links::getAcfmlExpertDoc( [ 'utm_content' => 'notification' ] ) . '" class="wpml-external-link" target="_blank">',
												  '</a>'
											  )
											  . '</p>',
					],
					'okText'       => [
						Mode::TRANSLATION  => esc_html__( 'Translate in String Translation', 'acfml' ),
						Mode::LOCALIZATION => esc_html__( 'Go to String Translation', 'acfml' ),
						Mode::ADVANCED     => esc_html__( 'Go to String Translation', 'acfml' ),
					],
					'cancelText'   => [
						Mode::TRANSLATION  => TranslationJobHooks::isEnabled() ? esc_html__( 'Translate with the post', 'acfml' ) : esc_html__( 'Go back', 'acfml' ),
						Mode::LOCALIZATION => esc_html__( 'Go back', 'acfml' ),
						Mode::ADVANCED     => esc_html__( 'Go back', 'acfml' ),
					],
					'redirectOnOk' => self::getLinkToST( $fieldGroupId ),
				];

			case Package::STATUS_PARTIALLY_TRANSLATED:
			case Package::STATUS_FULLY_TRANSLATED:
			default:
				$content = Package::STATUS_PARTIALLY_TRANSLATED === $status
					? esc_html__( 'You already translated the labels in this field group. You can update these translations on the String Translation page.', 'acfml' )
					: esc_html__( 'You already translated all field labels. You can update these translations on the String Translation page.', 'acfml' );

				return [
					'title'        => esc_html__( 'Translating field labels', 'acfml' ),
					'content'      => '<p>' . $content . '</p>',
					'okText'       => esc_html__( 'Go to String Translation', 'acfml' ),
					'cancelText'   => esc_html__( 'Go back', 'acfml' ),
					'redirectOnOk' => self::getLinkToST( $fieldGroupId ),
				];
		}
	}

	/**
	 * @param int $fieldGroupId
	 *
	 * @return string
	 */
	private static function getLinkToST( $fieldGroupId ) {
		return sprintf(
			admin_url( 'admin.php?page=wpml-string-translation/menu/string-translation.php&context=%s' ),
			Package::FIELD_GROUP_PACKAGE_KIND_SLUG . '-' . $fieldGroupId
		);
	}

	/**
	 * For simplicity and consistency, we'll escape all our strings
	 * on the backend and allow React render string with HTML.
	 *
	 * @param int         $attachedPosts
	 * @param null|string $nonTranslatableType
	 *
	 * @return array
	 */
	private static function getStrings( $attachedPosts, $nonTranslatableType ) {
		return [
			'fieldGroupMode' => [
				'title'                      => esc_html__( 'Select a translation option for this field group', 'acfml' ),
				'modes'                      => [
					'translation'  => [
						'label'       => esc_html__( 'Same fields across languages', 'acfml' ),
						'description' => '<p>' . esc_html__( "Translate your site’s content while keeping the same visual design across all languages. All languages will share the same field order, number of fields, and field types. All you need to do is translate the content.", 'acfml' ) . '</p>'
										. '<p>' . esc_html__( "You’ll be able to use automatic translation, WPML’s Advanced Translation Editor, and translation services.", 'acfml' ) . '</p>',
					],
					'localization' => [
						'label'       => esc_html__( 'Different fields across languages', 'acfml' ),
						'description' => '<p>' . esc_html__( "Use a different visual design in different languages. You can have a different field order, layout, and number of fields per language. With this option, you cannot use WPML’s Advanced Translation Editor or translation services.", 'acfml' ) . '</p>'
										. '<p>' . esc_html__( "You’ll need to create each translation with the native WordPress editor.", 'acfml' ) . '</p>',
					],
					'advanced'     => [
						'label'       => esc_html__( 'Expert', 'acfml' ),
						'description' => '<p>' . esc_html__( "If you are migrating a site, your existing field groups will use the Expert setup. This allows you to manually choose the translation option for each field in the group.", 'acfml' ) . '</p>'
										 /* translators: %1$s and %2$s will wrap the string in a <b> html tag */
										 . '<p>' . sprintf( esc_html__( 'This option is %1$snot recommended%2$s for new field groups.', 'acfml' ), '<b>', '</b>' ) . '</p>'
										 . '<p><a href="' . Links::getAcfmlExpertDoc() . '" class="wpml-external-link" target="_blank">' . esc_html__( 'Expert setup documentation', 'acfml' ) . '</a></p>',
					],
				],
				'choose'                     => esc_html__( 'Choose', 'acfml' ),
				'change-option'              => esc_html__( 'Change option', 'acfml' ),
				'acfml1tooltip'              => [
					'title'   => esc_html__( 'A Much Simpler Way to Translate Your ACF Sites', 'acfml' ),
					'content' => esc_html__( 'This new release of ACFML allows you to configure multilingual sites in one-click, instead of many complex settings. Choose how to setup the translation for the fields.', 'acfml' ),
				],
				'missing-mode'               => [
					'title'       => esc_html__( 'Select a Translation Option', 'acfml' ),
					'description' => esc_html__( 'Select a translation option in the Multilingual Setup section to save your changes.', 'acfml' ),
					'ok'          => esc_html__( 'OK', 'acfml' ),
				],
				'translate-cpt'              => [
					'title'       => DetectNonTranslatableLocations::getTitle( $nonTranslatableType ),
					'description' => DetectNonTranslatableLocations::getDescription( $nonTranslatableType ),
					'ok'          => esc_html__( 'Go to WPML Settings', 'acfml' ),
					'cancel'      => esc_html__( 'Go back', 'acfml' ),
				],
				'translation-status-process' => [
					'title'       => esc_html__( 'Are you sure you want to change the translation option?', 'acfml' ),
					'description' => AttachedPosts::getProcessConfirmationMessage( $attachedPosts ),
					'yes'         => esc_html__( 'Yes, continue', 'acfml' ),
					'no'          => esc_html__( 'No, go back', 'acfml' ),
				],
				'need-help-choosing'         => esc_html__( 'Need help choosing?', 'acfml' ),
				'documentation'              => esc_html__( 'Documentation', 'acfml' ),
				'go-to-st'                   => esc_html__( 'How to translate field labels »', 'acfml' ),
			],
		];
	}

	/**
	 * @return array
	 */
	private static function getDocLinks() {
		return [
			'main'         => Links::getAcfmlMainDoc(),
			'translation'  => Links::getAcfmlMainModeTranslationDoc(),
			'localization' => Links::getAcfmlMainModeLocalizationDoc(),
			'advanced'     => Links::getAcfmlExpertDoc(),
		];
	}
}
