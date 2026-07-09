<?php

namespace ACFML\Options;

use ACFML\Helper\Fields;
use ACFML\Helper\PhpFunctions;
use ACFML\Post\NativeEditorTranslationHooks;
use ACFML\Strings\Factory;
use ACFML\Strings\Package;
use ACFML\Tools\AdminUrl;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;

class EditorHooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const NOTICE_PRIORITY = 9;
	const NOTICE_GROUP    = 'acfml';
	const NOTICE_ID       = 'acfml-editing-translated-options-notice';

	/**
	 * @var \SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @var \WPML_ACF_Worker $acfWorker
	 */
	private $acfWorker;

	/**
	 * @var string|null
	 */
	private $optionsPageId;

	/**
	 * @var array
	 */
	private $translationsQueue = [];

	public function __construct(
		\SitePress $sitepress,
		Factory $factory,
		\WPML_ACF_Worker $acfWorker
	) {
		$this->sitepress = $sitepress;
		$this->factory   = $factory;
		$this->acfWorker = $acfWorker;
	}

	public function add_hooks() {
		add_action( 'admin_init', [ $this, 'setCurrentOptionsPage' ] );
		add_filter( 'acf/pre_render_fields', [ $this, 'preRenderOnTranslatedOptionsPage' ], 11, 2 );
		add_filter( 'acf/update_value', [ $this, 'onUpdateValue' ], 10, 3 );
		add_action( 'acf/options_page/save', [ $this, 'onUpdateOptionsPage' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueFieldLockAssets' ] );
		add_action( 'admin_notices', [ $this, 'editingTranslatedOptionsNotice' ], self::NOTICE_PRIORITY );
	}

	/** @return bool */
	private function isMainLanguage() {
		return $this->sitepress->get_current_language() === $this->sitepress->get_default_language();
	}

	public function setCurrentOptionsPage() {
		global $plugin_page;
		$optionsPage = acf_get_options_page( $plugin_page );
		if ( ! (bool) $optionsPage ) {
			return;
		}

		if ( ! isset( $_GET['lang'] ) ) { // phpcs:ignore
			$lang = apply_filters( 'wpml_current_language', null );
			$url  = add_query_arg( 'lang', $lang );

			wp_safe_redirect( $url );
			PhpFunctions::phpExit();
		}

		$this->optionsPageId = $optionsPage['post_id'];
	}

	/**
	 * @param array      $fields
	 * @param string|int $postId
	 *
	 * @return array
	 */
	public function preRenderOnTranslatedOptionsPage( $fields, $postId ) {
		if ( ! $this->optionsPageId ) {
			return $fields;
		}

		if ( $this->isMainLanguage() ) {
			return $fields;
		}

		NativeEditorTranslationHooks::loadFieldLockFilters();
		add_filter( 'acf/load_value', Fns::withNamedLock( self::class, Fns::identity(), function( $value, $fieldPostId, $field ) use ( $postId ) {
			if ( ! $this->optionsPageId ) {
				return $value;
			}
			if ( $postId !== $fieldPostId ) {
				return $value;
			}
			if ( $this->optionsPageId === $fieldPostId ) {
				return $value;
			}
			if ( Fields::isWrapperOrGroup( $field ) ) {
				return $value;
			}

			$currentLanguage = $this->sitepress->get_current_language();
			if ( WPML_COPY_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) ) {
				return $this->convertRelationshipField( $this->getFieldValue( $this->optionsPageId, $field ), $field, $currentLanguage );
			}

			if ( WPML_COPY_ONCE_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) ) {
				$storedValue = $this->getFieldValue( $fieldPostId, $field );
				if (
					false === $storedValue
					|| null === $storedValue
				) {
					$value = $this->convertRelationshipField( $this->getFieldValue( $this->optionsPageId, $field ), $field, $currentLanguage );
				}
				return $value;
			}

			return $value;
		} ), 10, 3 );
		return $fields;
	}

	/**
	 * @param string|int $postId
	 * @param string     $menuSlug
	 */
	public function onUpdateOptionsPage( $postId, $menuSlug ) {
		if ( $this->isMainLanguage() ) {
			return;
		}

		$this->processTranslationsQueue();
	}

	/**
	 * @param string|array $value
	 * @param string|int   $postId
	 * @param array        $field
	 */
	public function onUpdateValue( $value, $postId, $field ) {
		if ( ! $this->optionsPageId ) {
			return $value;
		}

		if ( $this->isMainLanguage() ) {
			// Saving an options page on a secundary language
			return $this->onUpdateMainValue( $value, $postId, $field );
		}
		return $this->onUpdateTranslationValue( $value, $postId, $field );
	}

	/**
	 * @param string|array $value
	 * @param string|int   $postId
	 * @param array        $field
	 */
	private function onUpdateMainValue( $value, $postId, $field ) {
		if ( Fields::isWrapperOrGroup( $field ) ) {
			$this->maybeCopyWrapperToTranslations( $value, $field );
			return $value;
		}

		if ( WPML_COPY_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) ) {
			$this->copyValueToTranslations( $value, $field );
			return $value;
		}

		if ( WPML_COPY_ONCE_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) ) {
			$this->copyValueToTranslations( $value, $field, false );
			return $value;
		}

		if ( WPML_TRANSLATE_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) && is_scalar( $value ) ) {
			$package = $this->factory->createPackage( $this->optionsPageId, Package::OPTION_PACKAGE_KIND_SLUG );
			$package->register( (string) $value, $this->getFieldData( $field, $value ) );
			return $value;
		}

		return $value;
	}

	/**
	 * Usually, wrapper fields hold a numeric, esoteric, placeholder value, just to bring it to existence.
	 *
	 * @param string|array $value
	 * @param array        $field
	 */
	private function maybeCopyWrapperToTranslations( $value, $field ) {
		if ( WPML_COPY_CUSTOM_FIELD === (int) Obj::prop( 'wpml_cf_preferences', $field ) ) {
			$optionName      = Obj::prop( 'name', $field );
			$optionKey       = Obj::prop( 'key', $field );
			$activeLanguages = Languages::getActive();
			foreach ( $activeLanguages as $languageCode => $language ) {
				if ( $languageCode === $this->sitepress->get_current_language() ) {
					continue;
				}
				$localOptionName = $this->optionsPageId . '_' . $languageCode . '_' . $optionName;
				update_option( $localOptionName, wp_unslash( $value ) );
				update_option( '_' . $localOptionName, $optionKey );
			}
		}
	}

	/**
	 * @param string|array $value
	 * @param array        $field
	 * @param bool         $overrideExisting
	 */
	private function copyValueToTranslations( $value, $field, $overrideExisting = true ) {
		$optionName      = Obj::prop( 'name', $field );
		$optionKey       = Obj::prop( 'key', $field );
		$activeLanguages = Languages::getActive();
		foreach ( $activeLanguages as $languageCode => $language ) {
			if ( $languageCode === $this->sitepress->get_current_language() ) {
				continue;
			}
			$localOptionName = $this->optionsPageId . '_' . $languageCode . '_' . $optionName;
			$localValue      = wp_unslash( $this->convertRelationshipField( $value, $field, $languageCode ) );
			if ( $overrideExisting ) {
				update_option( $localOptionName, $localValue );
				update_option( '_' . $localOptionName, $optionKey );
			} else {
				add_option( $localOptionName, $localValue );
				add_option( '_' . $localOptionName, $optionKey );
			}
		}
	}

	/**
	 * @param array        $field
	 * @param string|array $value
	 * 
	 * @return array
	 */
	private function getFieldData( $field, $value ) {
		return [
			'namespace' => PACKAGE::OPTION_PACKAGE_NAMESPACE,
			'id'        => Obj::prop( 'name', $field ),
			'key'       => Obj::prop( 'key', $field ),
			'title'     => Obj::prop( 'label', $field ),
			'type'      => $this->getValueType( $value ),
		];
	}

	/**
	 * @param string|array $value
	 * 
	 * @return string
	 */
	private function getValueType( $value ) {
		$type = 'LINE';
		if ( is_array( $value ) ) {
			$type = 'array';
		} elseif ( strip_tags( $value ) !== $value ) {
			$type = 'VISUAL';
		} elseif ( strpos( $value, "\n" ) !== false ) {
			$type = 'AREA';
		}
		return $type;
	}

	/**
	 * @param string|array $value
	 * @param string|int   $postId
	 * @param array        $field
	 */
	private function onUpdateTranslationValue( $value, $postId, $field ) {
		if ( $this->optionsPageId === $postId ) {
			return $value;
		}
		if ( Fields::isWrapperOrGroup( $field ) ) {
			return $value;
		}

		if ( WPML_COPY_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) ) {
			// Replace the value with the converted value from the default language.
			return $this->convertRelationshipField( $this->getFieldValue( $this->optionsPageId, $field ), $field, $this->sitepress->get_current_language() );
		}

		if ( WPML_TRANSLATE_CUSTOM_FIELD === Obj::prop( 'wpml_cf_preferences', $field ) && is_scalar( $value ) ) {
			// Register the value as the official translation for the value on the default language.
			$this->registerFieldTranslation( (string) $value, $field, $this->sitepress->get_current_language() );
			return $value;
		}

		return $value;
	}

	/**
	 * @param string|array $value
	 * @param array        $field
	 * @param string       $language
	 */
	private function registerFieldTranslation( $value, $field, $language ) {
		$originalValue = $this->getFieldValue( $this->optionsPageId, $field );
		if ( null === $originalValue || false === $originalValue || ! is_scalar( $originalValue ) ) {
			return;
		}
		$stringName    = Package::getStringName( $originalValue, $this->getFieldData( $field, $originalValue ) );
		$this->addToTranslationsQueue( $stringName, $language, $value );
	}

	/**
	 * @param string       $stringName
	 * @param string       $language
	 * @param string|array $value
	 */
	private function addToTranslationsQueue( $stringName, $language, $value ) {
		if ( ! array_key_exists( $stringName, $this->translationsQueue ) ) {
			$this->translationsQueue[ $stringName ] = [];
		}
		$this->translationsQueue[ $stringName ][ $language ] = [
			'value'  => $value,
			'status' => ICL_STRING_TRANSLATION_COMPLETE,
		];
	}

	private function processTranslationsQueue() {
		if ( empty( $this->translationsQueue ) ) {
			return;
		}
		$package = $this->factory->createPackage( $this->optionsPageId, Package::OPTION_PACKAGE_KIND_SLUG );
		$package->setStringTranslations( $this->translationsQueue );
		$package->flushCache();
		// TODO The CTE/ATE editors show the original translation once it is edited and saved in the options page on secondary language!
		// This is stored in the job at the icl_translate table on the field_data_translated column where it stores the previous translation :-/
	}

	/**
	 * Translate the value of relationship fields..
	 *
	 * @param string|array $value
	 * @param array        $field
	 * @param string       $language
	 *
	 * @return mixed
	 */
	private function convertRelationshipField( $value, $field, $language ) {
		return $this->acfWorker->convertMetaValue( $value, $field['name'], $field['type'], 'post', $this->optionsPageId, $this->optionsPageId . '_' . $language, $language );
	}

	public function enqueueFieldLockAssets() {
		if ( ! $this->optionsPageId ) {
			return;
		}

		if ( $this->isMainLanguage() ) {
			return;
		}

		NativeEditorTranslationHooks::enqueueAssets();
	}

	/**
	 * @param string|int $postId
	 * @param array      $field
	 *
	 * @return mixed
	 */
	private function getFieldValue( $postId, $field ) {
		if ( function_exists( 'acf_get_metadata_by_field' ) ) {
			// Introduced in ACF 6.4, avoids filters that could potentially cause infinite recursion.
			return acf_get_metadata_by_field( $postId, $field );
		}
		return acf_get_value( $postId, $field );
	}

	public function editingTranslatedOptionsNotice() {
		if ( ! $this->optionsPageId ) {
			return;
		}
		if ( ! function_exists( 'wpml_get_admin_notices' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$noticeId = md5( self::NOTICE_ID );
		$notices  = wpml_get_admin_notices();
		$notices->remove_notice( self::NOTICE_GROUP, $noticeId );

		if ( $this->isMainLanguage() ) {
			return;
		}

		$tmDashboardUrl = AdminUrl::getWPMLTMDashboardPackageSection( Package::OPTION_PACKAGE_KIND_SLUG );
		$tmDashboardUrl = add_query_arg( [ 'lang' => $this->sitepress->get_default_language(), 'admin_bar' => 1 ], $tmDashboardUrl );

		$text  = '<h2>' . esc_html__( 'Translate this Options page from the Translation Dashboard', 'acfml' ) . '</h2>';
		$text .= '<p>' . sprintf(
			/* translators: The placeholders are replaced by an HTML link pointing to the Translation Dashboards. */
			esc_html__( 'You no longer need to switch the admin language to translate options manually. Translate all your options from the %1$sTranslation Dashboard%2$s.', 'acfml' ),
			'<a href="' . esc_url( $tmDashboardUrl ) . '" title="' . esc_html__( 'Go to the Translation Dashboard', 'acfml' ) . '">', // phpcs:ignore
			'</a>'
			) . '</p>';

		$notice = $notices->create_notice( $noticeId, $text, self::NOTICE_GROUP );
		$notice->set_css_class_types( 'info' );
		$notice->set_dismissible( true );
		$notice->set_restrict_to_screen_ids( [ $screen->id ] );
		$notices->add_notice( $notice );
	}

}
