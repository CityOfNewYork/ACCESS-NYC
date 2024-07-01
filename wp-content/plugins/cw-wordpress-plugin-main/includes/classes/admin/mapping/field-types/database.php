<?php

namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Views\View;

class Database extends Base implements Type {

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text',
		'text_rich',
		'text_plain',
		'choice_radio',
	);

	protected $type_id = 'wp-type-database';
	protected $post_options = [];

	protected $tableColumnData = [];

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->tableColumnData = $this->getTableColumns();

		$tableNames         = array_keys( $this->tableColumnData );
		$this->post_options = array_combine( $tableNames, $tableNames );
		$this->option_label = __( 'Database', 'gathercontent-import' );
	}

	private function getAllTableColOptions() {
		$allOpts = [];

		foreach ( $this->tableColumnData as $tableName => $columns ) {
			$allOpts = array_merge( $allOpts, $this->getTableColOptions( $tableName ) );
		}

		return $allOpts;
	}

	private function getTableColOptions( string $tableName ) {
		$optionStrings = [];

		foreach ( $this->tableColumnData[ $tableName ] as $column ) {
			/**
			 * Returns the required underscore template string to be true when
			 * this column and table are selected. We need to check for both as
			 * multiple tables can use the same column name
			 */
			$isColumnAndTableTemplateJs = "data.field_value == '{$tableName}.{$column}'";

			/**
			 * template engine needs to know if this should be set selected
			 */
			$selectedJs = "
				<# if ($isColumnAndTableTemplateJs) { #> selected='selected' <# } #>
			";

			/**
			 * Template engine always set visible if this is the selected column
			 */
			$defaultVisibleJs = "
				style='display: <# if ($isColumnAndTableTemplateJs) { #>block<# } else { #>none<# } #>'
			";

			$optionData = "data-tablename='$tableName' data-columnname='$column'";

			$optionStrings[] = "<option $selectedJs $defaultVisibleJs $optionData value='{$column}'>{$column}</option>";
		}

		return $optionStrings;
	}

	public function underscore_options( $array ) {
		foreach ( $array as $value => $label ) {
			$this->underscore_option( $value, $label );
		}
	}

	public function underscore_option( $value, $label ) {
		$fieldValueJs = "
			<# if ( '" . $value . "' === (data.field_value ? data.field_value : '').split('.')[0] ) { #>selected='selected'<# } #>
		";

		echo '<option ' . esc_attr( $fieldValueJs ) . ' value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
	}

	/**
	 * Returns valid table -> columns for this input. Only tables that include
	 * a 'post_id' column.
	 *
	 * @return Array<string, string[]> - [tableName => colNames, ...]
	 */
	private function getTableColumns() {
		global $wpdb;

		$wpTables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

		$allColumns = [];
		foreach ( $wpTables as $tableName ) {
			$tableCols = $wpdb->get_results( "SHOW COLUMNS FROM $tableName" );

			$columnNames = [];
			foreach ( $tableCols as $col ) {
				$columnNames[] = $col->Field;
			}

			/**
			 * We are only interested in tables that contain a 'post_id' column
			 */
			if ( ! in_array( 'post_id', $columnNames ) ) {
				continue;
			}

			unset( $columnNames[ array_search( 'post_id', $columnNames ) ] );

			$allColumns[ $tableName ] = $columnNames;
		}

		return $allColumns;
	}

	public function underscore_template( View $view ) {

		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
		<div class="wp-type-database-dropdown-container">
			<select
				class="cw-table-selector gc-select2 wp-type-value-select <?php $this->e_type_id(); ?>"
				name=""
			>
				<?php $this->underscore_options( $this->post_options ); ?>
				<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
			</select>

			<select
				class="cw-column-selector"
				name=""
			>
				<option value="">Select a column</option>

				<?php
				/**
				 * This is not escaped as it can contain various tags that we know are safe.
				 */
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( '\r\n', $this->getAllTableColOptions() );
				?>
			</select>

			<input
				class="hidden-database-table-name"
				type="hidden"
				name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]"
			<# if ( data.field_value ) { #>value="{{ data.field_value }}"<# } #>
			/>
		</div>
		<p class="description" style="font-size: 10px">
			You can only select tables that contain a <code style="font-size: 8px">post_id</code> column, as this is
			necessary to locate the corresponding row within the chosen table.
		</p>
		<# } #>
		<?php
	}

}
