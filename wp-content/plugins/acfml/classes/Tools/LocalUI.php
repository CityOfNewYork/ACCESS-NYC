<?php

namespace ACFML\Tools;

use ACFML\Strings\Factory;
use ACFML\Strings\Translator;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;

class LocalUI {

	const NONCE = 'nonce_acfml_tools_local_settings';

	const POST_SCAN_MODE             = 'acfml-scan-mode';
	const POST_REGISTER_LOCAL_LABELS = 'acfml-register-local-labels';

	const SCAN_MODE_NONE   = 'none';
	const SCAN_MODE_ONCE   = 'once';
	const SCAN_MODE_ALWAYS = 'always';

	const REGISTER_LOCAL_LABELS_NONE = 'none';
	const REGISTER_LOCAL_LABELS_ONCE = 'once';

	/**
	 * @var string name
	 */
	public $name = 'acfml-local-settings';

	/**
	 * Title of the ACF/ACFML Tool page.
	 *
	 * @var string title
	 */
	public $title = '';

	public function __construct() {
		$this->title = __( 'Translate ACF Local JSON and PHP-Registered Fields', 'acfml' );
	}

	public function initialize() {
	}

	public function load() {
	}

	public function html() {
		?>
		<div class="acf-postbox-header">
			<h2 class="acf-postbox-title"><?php echo esc_html( $this->title ); ?></h2>
		</div>
		<div class="acf-postbox-inner">
			<p>
			<?php
			echo sprintf(
				/* translators: %1$s, %2$s, %3$s and %4$s are placeholders for two <a> link tags. */
				esc_html__( 'ACF allows you to %1$sregister fields via PHP%2$s or %3$ssave field settings as JSON files%4$s. You can also save post types, taxonomies, or options pages settings to JSON files.', 'acfml' ),
				'<a href="https://www.advancedcustomfields.com/resources/register-fields-via-php/" target="_blank">',
				'</a>',
				'<a href="https://www.advancedcustomfields.com/resources/local-json/" target="_blank">',
				'</a>'
			);
			?>
			</p>
			<p>
				<?php esc_html_e( 'Configure how ACF Multilingual handles translations for these items.', 'acfml' ); ?>
			</p>
			<div class="acf-fields">
				<div class="acf-field">
					<h3><?php esc_html_e( 'Sync Translation Preferences for Local Fields', 'acfml' ); ?></h3>
					<ul class="acf-checkbox acf-bl">
						<li>
							<label>
								<input name="<?php echo esc_attr( self::POST_SCAN_MODE ); ?>" type="radio" value="<?php echo esc_attr( self::SCAN_MODE_NONE ); ?>" <?php checked( self::SCAN_MODE_NONE, LocalSettings::getScanMode() ); ?> />
								<?php esc_html_e( 'Don’t sync translation preferences', 'acfml' ); ?>
							</label>
						</li>
						<li>
							<label>
								<input name="<?php echo esc_attr( self::POST_SCAN_MODE ); ?>" type="radio" value="<?php echo esc_attr( self::SCAN_MODE_ONCE ); ?>" />
								<?php esc_html_e( 'Sync once now', 'acfml' ); ?>
							</label>
						</li>
						<li>
							<label>
								<input name="<?php echo esc_attr( self::POST_SCAN_MODE ); ?>" type="radio" value="<?php echo esc_attr( self::SCAN_MODE_ALWAYS ); ?>" <?php checked( self::SCAN_MODE_ALWAYS, LocalSettings::getScanMode() ); ?> />
								<?php esc_html_e( 'Sync on every request (may affect performance)', 'acfml' ); ?>
							</label>
						</li>
					</ul>
				</div>

				<div class="acf-field">
					<h3><?php esc_html_e( 'Register Labels for Translation', 'acfml' ); ?></h3>
					<p>
					<?php
					esc_html_e( 'Scans post types, taxonomies, options pages, and field groups stored in the "acf-json" directory, then registers their labels with WPML so you can translate them.', 'acfml' );
					?>
					</p>
					<ul class="acf-checkbox acf-bl">
						<li>
							<label>
								<input name="<?php echo esc_attr( self::POST_REGISTER_LOCAL_LABELS ); ?>" type="radio" value="<?php echo esc_attr( self::REGISTER_LOCAL_LABELS_NONE ); ?>" checked="checked" />
								<?php esc_html_e( 'Don’t register labels', 'acfml' ); ?>
							</label>
						</li>
						<li>
							<label>
								<input name="<?php echo esc_attr( self::POST_REGISTER_LOCAL_LABELS ); ?>" type="radio" value="<?php echo esc_attr( self::REGISTER_LOCAL_LABELS_ONCE ); ?>" />
								<?php esc_html_e( 'Register labels now', 'acfml' ); ?>
							</label>
						</li>
					</ul>
				</div>
			</div>

			<p class="acf-submit">
				<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
				<button type="submit" name="acfml-submit-local-tool" value="acfml-submit-local-tool" class="acf-btn"><?php esc_attr_e( 'Apply', 'acfml' ); ?></button>
			</p>
		</div>
		<?php
	}

	public function submit() {
		// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$nonceValue = sanitize_key( Obj::prop( self::NONCE, $_POST ) );
		if ( ! wp_verify_nonce( $nonceValue, self::NONCE ) ) {
			return;
		}

		// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$scanMode = sanitize_key( Obj::propOr( self::SCAN_MODE_NONE, self::POST_SCAN_MODE, $_POST ) );
		if ( ! in_array( $scanMode, [ self::SCAN_MODE_NONE, self::SCAN_MODE_ONCE, self::SCAN_MODE_ALWAYS ], true ) ) {
			$scanMode = self::SCAN_MODE_NONE;
		}
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$registerLocalLabels = self::REGISTER_LOCAL_LABELS_ONCE === Obj::prop( self::POST_REGISTER_LOCAL_LABELS, $_POST );

		$successNotice = [];

		switch ( $scanMode ) {
			case self::SCAN_MODE_NONE:
				LocalSettings::enableScanMode( false );
				$successNotice[] = __( 'The synchronization of translation preferences is disabled.', 'acfml' );
				break;
			case self::SCAN_MODE_ONCE:
				LocalSettings::enableScanMode( false );
				$successNotice[] = __( 'Translation preferences synchronized.', 'acfml' );
				break;
			case self::SCAN_MODE_ALWAYS:
				LocalSettings::enableScanMode( true );
				$successNotice[] = __( 'The synchronization of translation preferences is enabled.', 'acfml' );
				break;
		}

		if ( $registerLocalLabels ) {
			$isLocalEnabled = acf_is_local_enabled();
			if ( ! $isLocalEnabled ) {
				acf_enable_local();
			}
			$translator                    = new Translator( new Factory() );
			$affectedItems                 = [];
			$affectedItems['group']        = $this->registerLocalItems( 'acf-field-group', [ $translator, 'registerGroupAndFieldsAndLayouts' ] );
			$affectedItems['post-type']    = $this->registerLocalItems( 'acf-post-type', [ $translator, 'registerCPT' ] );
			$affectedItems['taxonomy']     = $this->registerLocalItems( 'acf-taxonomy', [ $translator, 'registerTaxonomy' ] );
			$affectedItems['options-page'] = $this->registerLocalItems( 'acf-ui-options-page', [ $translator, 'registerOptionsPage' ] );
			if ( ! $isLocalEnabled ) {
				acf_disable_local();
			}

			$successNotice[] = $this->getRegisteredLabelsNotice( $affectedItems );
		}

		acf_add_admin_notice( implode( ' ', $successNotice ), 'success' );
	}

	/**
	 * @param array $item
	 *
	 * @return bool
	 */
	public function isLocal( $item ) {
		return Relation::propEq( 'ID', 0, $item ) && Lst::includes( Obj::prop( 'local', $item ), [ 'json', 'php' ] );
	}

	/**
	 * @param string   $itemType
	 * @param callable $registrationMethod
	 *
	 * @return int
	 */
	private function registerLocalItems( $itemType, $registrationMethod ) {
		return wpml_collect( acf_get_internal_post_type_posts( $itemType ) )
			->filter( [ $this, 'isLocal' ] )
			->map( $registrationMethod )
			->count();
	}

	/**
	 * @param int    $count
	 * @param string $type
	 *
	 * @return string
	 */
	private function getAffectedNoticeBit( $count, $type ) {
		switch ( $type ) {
			case 'group':
				/* translators: Used to show the number of field groups */
				return sprintf( _n( '%s group', '%s groups', $count, 'acfml' ), $count );
			case 'post-type':
				/* translators: Used to show the number of post types */
				return sprintf( _n( '%s post type', '%s post types', $count, 'acfml' ), $count );
			case 'taxonomy':
				/* translators: Used to show the number of taxonomies */
				return sprintf( _n( '%s taxonomy', '%s taxonomies', $count, 'acfml' ), $count );
			case 'options-page':
				/* translators: Used to show the number of options pages */
				return sprintf( _n( '%s options page', '%s options pages', $count, 'acfml' ), $count );
		}

		return '';
	}

	/**
	 * @param array<string,int> $affectedItems
	 *
	 * @return string
	 */
	private function getRegisteredLabelsNotice( $affectedItems ) {
		if ( 0 === array_sum( $affectedItems) ) {
			return __( 'No local field groups, post types, taxonomies, or Options pages were found.', 'acfml' );
		}

		$successNoticeBits = wpml_collect( $affectedItems )
			->filter()
			->map( function( $count, $type ) {
				return $this->getAffectedNoticeBit( $count, $type );
			} )
			->toArray();

		if ( count( $successNoticeBits ) < 2 ) {
			/* translators: Used between elements of a two elements list */
			$noticeList = implode( _x( ' and ', 'Used between elements of a two elements list', 'acfml' ), $successNoticeBits );
		} else {
			$last  = array_slice( $successNoticeBits, - 1 );
			$first = implode( ', ', array_slice( $successNoticeBits, 0, - 1 ) );
			$both  = array_merge( array( $first ), $last );
			/* translators: Used before the last element of a three or more elements list */
			$noticeList = implode( _x( ', and ', 'Used before the last element of a three or more elements list', 'acfml' ), $both );
		}

		return sprintf(
			__( 'Successfully registered labels for %s.', 'acfml' ),
			$noticeList
		);
	}

}
