<?php
namespace GatherContent\Importer\Admin\Mapping;
use GatherContent\Importer\Mapping_Post;

/**
 * Class for managing syncing template items.
 *
 * @since 3.0.0
 */
class Items_Sync extends Base {

	/**
	 * Template_Mappings
	 *
	 * @var Template_Mappings
	 */
	public $mappings;

	/**
	 * Mapping_Post
	 *
	 * @var Mapping_Post
	 */
	protected $mapping;

	protected $items = array();
	protected $url = '';

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->mappings = $args['mappings'];
		$this->items    = array_values( array_map( array( $this, 'prepare_for_js' ), $args['items'] ) );
		$this->url      = $args['url'];

		$this->mapping = Mapping_Post::get( $this->mapping_id );

		add_filter( 'gc_admin_notices', array( $this, 'register_import_errors' ) );
	}

	public function register_import_errors( $notices ) {
		if ( ! $this->_get_val( 'sync-items' ) || 1 !== absint( $this->_get_val( 'sync-items' ) ) ) {
			return;
		}

		$last_error = $this->mapping->get_meta( 'last_error' );
		$item_errors = $this->mapping->get_meta( 'item_errors' );

		if ( $last_error ) {
			$msg = '';

			$parts = $this->error_parts( $last_error );
			$msg .= array_shift( $parts );
			foreach ( $parts as $part ) {
				$msg .= '</strong></p>';
				$msg .= $part;
				$msg .= '<p><strong><button type="button" class="button gc-notice-dismiss" id="dismiss-item-import-errors">' . __( 'Dismiss', 'gathercontent-import' ) . '</button>';
			}

			$notices[] = array(
				'id'      => 'gc-import-last-error',
				'message' => $msg,
			);
		}

		if ( $item_errors ) {
			if ( is_array( $item_errors ) ) {
				$msg = '';
				$main = __( 'There were some errors with the item import:', 'gathercontent-import' );
				$msg .= '<ul>';
				foreach ( $item_errors as $error ) {
					$parts = $this->error_parts( $error );
					// $main = array_shift( $parts );
					$msg .= '<li>' . implode( "\n", $parts ) . '</li>';
				}
				$msg .= '</ul>';

				$msg = $main . '</strong></p>' . $msg . '<p><strong><button type="button" class="button gc-notice-dismiss" id="dismiss-item-import-errors">' . __( 'Dismiss', 'gathercontent-import' ) . '</button>';

				$notices[] = array(
					'id'      => 'gc-import-errors',
					'message' => $msg,
				);
			}
		}

		return $notices;
	}

	protected function error_parts( $error ) {
		$msg_parts = array();

		if ( is_wp_error( $error ) ) {

			$msg_parts[] = sprintf(
				'[%s] %s',
				$error->get_error_code(),
				$error->get_error_message()
			);

			$msg_parts[] = '<xmp style="display:none;">' . print_r( $error->get_error_data(), true ) . '</xmp>';

		} else {
			$msg_parts[] = __( 'Error!', 'gathercontent-import' );
			$msg_parts[] = '<xmp style="display:none;"> '. print_r( $error, true ) .' </xmp>';
		}

		return $msg_parts;
	}

	public function prepare_for_js( $item ) {
		return \GatherContent\Importer\prepare_item_for_js( $item, $this->mapping_id );
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-sync';
	}

	/**
	 * The sync page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function ui_page() {
		// Output the markup for the JS to build on.
		?>
		<input type="hidden" name="mapping_id" id="gc-input-mapping_id" value="<?php echo $this->mapping_id; ?>"/>
		<?php foreach ( $_GET as $key => $value ) : if ( 'mapping' === $key ) { continue; } ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" id="gc-input-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<?php endforeach; ?>
		<p class="gc-submit-top"><input type="submit" name="submit" id="gc-submit-2" class="button button-primary button-large" value="<?php esc_html_e( 'Import Selected Items', 'gathercontent-import' ); ?>"></p>
		<div id="gc-items-search"></div>
		<div id="sync-tabs"><span class="gc-loader spinner is-active"></span></div>
		<p class="description">
			<a href="<?php echo $this->mapping->get_edit_post_link(); ?>"><?php echo $this->mappings->args->labels->edit_item; ?></a>
		</p>
		<?php
	}

	/**
	 * Get the localizable data array.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of localizable data
	 */
	protected function get_localize_data() {
		return array(
			'percent' => $this->mapping->get_pull_percent(),
			'_items'  => $this->items,
			'_text'   => array(
				'no_items' => esc_html__( 'No items found.', 'gathercontent-import' ),
			),
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		return array(
			'tmpl-gc-table-search' => array(),
			'tmpl-gc-table-nav' => array(),
			'tmpl-gc-items-sync' => array(
				'headers' => array(
					'status'      => __( 'Status', 'gathercontent-import' ),
					'itemName'    => __( 'Item', 'gathercontent-import' ),
					'updated_at'  => __( 'Updated', 'gathercontent-import' ),
					'mappingName' => __( 'Template Mapping', 'gathercontent-import' ),
					'post_title'  => __( 'WordPress Title', 'gathercontent-import' ),
				),
			),
			'tmpl-gc-item' => array(
				'url' => $this->url,
			),
			'tmpl-gc-items-sync-progress' => array(),
		);
	}

}
