<?php

namespace WPML\Notices\ExportImport;

class Notice implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	// Cascade of priorities before 10.
	// 7: WPML.
	// 8: WCML.
	// 9: WPML Export and Import.
	const PRIORITY       = 7;
	const GROUP          = 'wpml-import-notices';
	const NOTICE_CLASSES = [
		'wpml-import-notice',
		'wpml-import-notice-from-wpml',
	];

	const WPML_IMPORT_URL = 'https://wpml.org/documentation/related-projects/wpml-export-and-import/?utm_source=plugin&utm_medium=gui&utm_campaign=wpml-export-import&utm_term=admin-notice';
	const WCML_URL        = 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/?utm_source=plugin&utm_medium=gui&utm_campaign=wcml&utm_term=admin-notice';

	const EXPORT_NOTICES = [
		'wordpress-export'        => '/export.php',
		'wp-import-export-export' => '/admin.php?page=wpie-new-export',
		'wp-all-export'           => '/admin.php?page=pmxe-admin-export',
		'woocommerce-export'      => '/edit.php?post_type=product&page=product_exporter',
	];
	const IMPORT_NOTICES = [
		'wordpress-import'        => '/admin.php?import=wordpress',
		'wp-import-export-import' => '/admin.php?page=wpie-new-import',
		'wp-all-import'           => '/admin.php?page=pmxi-admin-import',
		'woocommerce-import'      => '/edit.php?post_type=product&page=product_importer',
	];

	/** @var \WPML_Notices $notices */
	private $wpmlNotices;

	public function __construct( \WPML_Notices $wpmlNotices ) {
		$this->wpmlNotices = $wpmlNotices;
	}

	public function add_hooks() {
		if ( defined( 'WPML_IMPORT_VERSION' ) ) {
			// WPML Export and Import will take care of this.
			return;
		}

		add_action( 'admin_head', [ $this, 'enforceNoticeStyle' ] );
		add_action( 'admin_init', [ $this, 'manageNotice' ], self::PRIORITY );
	}

	public function manageNotice() {
		if ( ! self::isOnMigrationPages() ) {
			return;
		}
		$this->wpmlNotices->remove_notice_group( self::GROUP );
		$exportNotices = self::EXPORT_NOTICES;
		array_walk( $exportNotices, function( $path, $id ) {
			$this->maybeAddNotice(
				$id,
				$path,
				$this->getExportMessage( $id )
			);
		} );
		$importNotices = self::IMPORT_NOTICES;
		array_walk( $importNotices, function( $path, $id ) {
			$this->maybeAddNotice(
				$id,
				$path,
				$this->getImportMessage( $id )
			);
		} );
	}

	/**
	 * @param string $id
	 * @param string $path
	 * @param string $message
	 */
	private function maybeAddNotice( $id, $path, $message ) {
		if ( ! self::isOnPage( $path ) ) {
			return;
		}
		$notice = $this->wpmlNotices->get_new_notice(
			$id,
			$message,
			self::GROUP
		);
		$notice->set_css_class_types( 'info' );
		$notice->set_css_classes( self::NOTICE_CLASSES );
		$notice->add_display_callback( [ \WPML\Notices\ExportImport\Notice::class, 'isOnMigrationPages' ] );
		$notice->set_dismissible( true );
		$this->wpmlNotices->add_notice( $notice, true );
	}

	public function enforceNoticeStyle() {
		if ( ! self::isOnMigrationPages() ) {
			return;
		}

		echo '<style>
		.notice.wpml-import-notice {
			display: block !important;
			margin: 25px 25px 25px 0;
			padding: 24px 24px 24px 62px;
		}
		.notice.wpml-import-notice::before {
			line-height: 20px;
		}
		.notice.wpml-import-notice .otgs-notice-actions {
			margin-top: 24px;
		}
		.notice.wpml-import-notice .notice-action {
			margin-right: 12px;
		}
		</style>';
	}

	/**
	 * @param  string $path
	 *
	 * @return bool
	 */
	private static function isOnPage( $path ) {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		if (
			defined( 'DOING_AJAX' )
			&& DOING_AJAX
		) {
			return false;
		}

		if ( false !== strpos( $_SERVER['REQUEST_URI'], $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isOnMigrationPages() {
		$exportNotices = self::EXPORT_NOTICES;
		$importNotices = self::IMPORT_NOTICES;
		$migrationPaths = array_values( array_merge( $exportNotices, $importNotices ) );
		foreach ( $migrationPaths as $path ) {
			if ( false !== self::isOnPage( $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param  string $id
	 *
	 * @return bool
	 */
	private function isNoticeForShop( $id ) {
		if ( in_array( $id, [ 'woocommerce-export', 'woocommerce-import' ] ) ) {
			return true;
		}

		if ( 'wp-all-export' === $id && defined ( 'PMWE_VERSION' ) ) {
			return true;
		}

		if ( 'wp-all-import' === $id && defined ( 'PMWI_VERSION' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param  string $id
	 *
	 * @return string
	 */
	private function getExportMessage( $id ) {
		if ( $this->isNoticeForShop( $id ) ) {
			return $this->getShopExportMessage();
		}
		return sprintf(
			/* translators: %s is a link. */
			__( 'Migrating your multilingual site? Install %s for the simplest way to export your translated content and import it on your new site.', 'sitepress' ),
			$this->getWpmlImportLink()
		);
	}

	/**
	 * @return string
	 */
	private function getShopExportMessage() {
		return sprintf(
			/* translators: %s is a set of one or two links. */
			__( 'Migrating your multilingual shop? With %s you can transfer your translated content to a new site, including cross-sells, up-sells, and product attributes.', 'sitepress' ),
			$this->getShopLink()
		);
	}

	/**
	 * @param  string $id
	 *
	 * @return string
	 */
	private function getImportMessage( $id ) {
		if ( $this->isNoticeForShop( $id ) ) {
			return $this->getShopImportMessage();
		}
		return sprintf(
			/* translators: %s is a link. */
			__( 'Looking to import your multilingual content? Install %s in both your original and new site for the easiest way to export and import your content.', 'sitepress' ),
			$this->getWpmlImportLink()
		);
	}

	/**
	 * @return string
	 */
	private function getShopImportMessage() {
		return sprintf(
			/* translators: %1$s and %2$s are both links. */
			__( 'Looking to import your multilingual shop? With %1$s and %2$s in both your original and new site, you can export and import your translations automatically.', 'sitepress' ),
			$this->getWcmlLink(),
			$this->getWpmlImportLink()
		);
	}

	/**
	 * @return string
	 */
	private function getWpmlImportLink() {
		$url   = self::WPML_IMPORT_URL;
		$title = __( 'WPML Export and Import', 'sitepress' );
		return '<a class="wpml-external-link" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '" target="_blank">'
			. esc_html( $title )
			. '</a>';
	}

	/**
	 * @return string
	 */
	private function getWcmlLink() {
		$url   = self::WCML_URL;
		$title = __( 'WooCommerce Multilingual', 'sitepress' );
		return '<a class="wpml-external-link" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '" target="_blank">'
			. esc_html( $title )
			. '</a>';
	}

	/**
	 * @return string
	 */
	private function getShopLink() {
		if ( defined( 'WCML_VERSION' ) ) {
			return $this->getWpmlImportLink();
		}
		return sprintf(
			/* translators: %1$s and %2$s are links. */
			__( '%1$s and %2$s', 'sitepress' ),
			$this->getWcmlLink(),
			$this->getWpmlImportLink()
		);
	}

}
