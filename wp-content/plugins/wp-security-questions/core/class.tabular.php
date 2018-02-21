<?php
/**
 * WP_List_Table_Helper Class File.
 * @package Core
 * @author Flipper Code <hello@flippercode.com>
 */

if ( ! class_exists( 'WP_List_Table_Helper' ) ) {

	/**
	 * Include the main wp-list-table file.
	 */

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ); }

	/**
	 * Extend WP_LIST_TABLE to simplify table listing.
	 * @package Core
	 * @author Flipper Code <hello@flippercode.com>
	 */
	class WP_List_Table_Helper extends WP_List_Table {

		/**
	 * Table name.
	 * @var string
	 */
		var $table;
		/**
	 * Custom SQL Query to fetch records.
	 * @var string
	 */
		var $sql;
		/**
	 * Action over records.
	 * @var array
	 */
		var $actions = array( 'edit','delete' );
		/**
	 * Timestamp Column in table.
	 * @var string
	 */
		var $currenttimestamp_field;
		/**
	 * Text Domain for multilingual.
	 * @var string
	 */
		var $textdomain;
		/**
	 * Singular label.
	 * @var string
	 */
		var $singular_label;
		/**
	 * Plural lable.
	 * @var string
	 */
		var $plural_label;
		/**
	 * Show add navigation at the top.
	 * @var boolean
	 */
		var $show_add_button = true;
		/**
	 * Ajax based listing
	 * @var boolean
	 */
		var $ajax = false;
		/**
	 * Columns to be displayed.
	 * @var array
	 */
		var $columns;
		/**
	 * Columns to be sortable.
	 * @var array
	 */
		var $sortable;
		/**
	 * Fields to be hide.
	 * @var  array
	 */
		var $hidden;
		/**
	 * Records per page.
	 * @var integer
	 */
		var $per_page = 10;
		/**
	 * Slug for the manage page.
	 * @var string
	 */
		var $admin_listing_page_name;
		/**
	 * Slug for the add or edit page.
	 * @var string
	 */
		var $admin_add_page_name;
		/**
	 * Response
	 * @var string
	 */
		var $response;
		/**
	 * Display string at the top of the table.
	 * @var string
	 */
		var $toptext;
		/**
	 * Display string at the bottom of the table.
	 * @var [type]
	 */
		var $bottomtext;
		/**
	 * Primary column of the table.
	 * @var string
	 */
		var $primary_col;
		/**
	 * Column where to display actions navigation.
	 * @var string
	 */
		var $col_showing_links;
		/**
	 * Call external function when actions executed.
	 * @var array
	 */
		var $extra_processing_on_actions;
		/**
	 * Current action name.
	 * @var string
	 */
		var $now_action;
		/**
	 * Table prefix.
	 * @var string
	 */
		var $prefix;
		/**
	 * Current page's records.
	 * @var array
	 */
		var $found_data;
		/**
	 * Total # of records.
	 * @var int
	 */
		var $items;
		/**
	 * All Records.
	 * @var array
	 */
		var $data;
		/**
	 * Columns to be excluded in search.
	 * @var array
	 */
		var $searchExclude;
		/**
	 * Actions executed in bulk action.
	 * @var array
	 */
		var $bulk_actions;

		/**
		 * Constructer method
		 * @param array $tableinfo Listing configurations.
		 */
		public function __construct($tableinfo) {

			global $wpdb;
			$this->prefix = $wpdb->prefix;

			foreach ( $tableinfo as $key => $value ) {    // Initialise constuctor based provided values to class variables.
				$this->$key = $tableinfo[ $key ];
			}

			parent::__construct( array(
				'singular'  => __( $this->singular_label, $this->textdomain ),
				'plural'    => __( $this->plural_label,   $this->textdomain ),
				'ajax'      => $this->ajax,
			) );

			$this->init_listing();

		}
		/**
		 * Initialize table listing.
		 */
		public function init_listing() {

			if ( ! empty( $this->currenttimestamp_field ) ) {  // Load extra resources if we want to show time based filters in listing table.

				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

			}
			$this->prepare_items();

			if ( ! empty( $_GET['doaction'] ) and ! empty( $_GET[ $this->primary_col ] ) ) {
				$this->now_action = $function_name = sanitize_text_field( wp_unslash( $_GET['doaction'] ) );
				if ( false != strpos( sanitize_text_field( wp_unslash( $_GET['doaction'] ) ), '-' ) ) {
					$function_name = str_replace( '-','',$function_name ); }
				$this->$function_name();

			} else {
				$this->listing();
			}

		}
		/**
		 * Edit action.
		 */
		public function edit() {

		}
		/**
		 * Delete action.
		 */
		public function delete() {

			global $wpdb;
			$id = intval( wp_unslash( $_GET[ $this->primary_col ] ) );
			$query = "DELETE FROM {$this->table} WHERE {$this->primary_col} = ".$id;
			$del = $wpdb->query( $query );
			$this->prepare_items();
			$this->response['success'] = __( 'Selected '.ucwords( $this->singular_label ).' Deleted Successfully.', $this->textdomain );
			$this->listing();

		}
		/**
		 * Display records listing.
		 */
		public function listing() {
		?>

		<div class="flippercode-ui">
	<div class="fc-main">
		<div class="fc-container">
          <div class="fc-divider">
			  <div class="fc-back">
            <div class="fc-12">
			<div class="fc_listing_menu_title">
				<h4 class="fc-title-blue"><?php _e( 'Manage '.ucwords( $this->plural_label ), $this->textdomain );?></h4>
			</div>
	 
	<div class="fc-tabular-overview">
	<?php $this->show_notification( $this->response ); ?>
	<fieldset>
	<form method="post" action="<?php echo admin_url( 'admin.php?page='.$this->admin_listing_page_name ); ?>">
	<?php
	$this->search_box( 'search', 'search_id' );
	$this->display();
	?>
	<input type="hidden" name="row_id" value="" />
	<input type="hidden" name="operation" value="" />
	<?php wp_nonce_field( 'wpgmp-nonce','_wpnonce',true,true ); ?>
</form>
</fieldset>
</div>
</div>
</div>
<a href="http://www.flippercode.com/forums" target="_blank" title="Ask Question" class="helpdask-bootom">Helpdesk ?</a>
</div>
</div>
</div>
<?php	}
		/**
		 * Reset primary column ID.
		 */
		public function unset_id_field() {

			if ( array_key_exists( $this->primary_col, $this->columns ) ) { unset( $this->columns[ $this->primary_col ] );	}
		}
		/**
		 * Get sortable columns.
		 * @return array Sortable columns names.
		 */
		function get_sortable_columns() {

			if ( empty( $this->sortable ) ) {

				$sortable_columns[ $this->primary_col ] = array( $this->primary_col,false );
			} else {

				foreach ( $this->sortable as $sortable ) {
					$sortable_columns[ $sortable ] = array( $sortable,false );
				}
			}
			return $sortable_columns;
		}
		/**
		 * Get columns to be displayed.
		 * @return array Columns names.
		 */
		function get_columns() {

			$columns = array( 'cb' => '<input type="checkbox" />' );

			if ( ! empty( $this->sql ) ) {
				global $wpdb;
				$results = $wpdb->get_results( $this->sql );
				if(is_array($results) and !empty($results)) {
					foreach ( $results[0] as $column_name => $column_value ) {    // Get all columns by provided returned by sql query(Preparing Columns Array).
					if(array_key_exists($column_name, $this->columns)) {
						$this->columns[ $column_name ] = $this->columns[$column_name];
					} else {
						$this->columns[ $column_name ] = $column_name;
					}
				}
				}
			} else {
				if ( empty( $this->columns ) ) {
					global $wpdb;
					foreach ( $wpdb->get_col( 'DESC ' . $this->table, 0 ) as $column_name ) {  // Query all column name usind DESC (Preparing Columns Array).
						$this->columns[ $column_name ] = $column_name;
					}
				}
			}

			$this->unset_id_field(); // Preventing Id field to showup in Listing.

			// This is how we initialise all columns dynamically instead of statically (normally we write each column name here) in get_columns function definition :).
			foreach ( $this->columns as $dbcolname => $collabel ) {
				$columns[ $dbcolname ] = __( $collabel, $this->textdomain );
			}

			return $columns;
		}
		/**
		 * Column where to display actions.
		 * @param  array  $item        Record.
		 * @param  string $column_name Column name.
		 * @return string              Column output.
		 */
		function column_default( $item, $column_name ) {
			// Return Default values from db except current timestamp field. If currenttimestamp_field is encountered return formatted value.
			if ( ! empty( $this->currenttimestamp_field ) and $column_name == $this->currenttimestamp_field ) {
				$return = date( 'F j, Y',strtotime( $item->$column_name ) );
			} else if ( $column_name == $this->col_showing_links ) {
				$actions = array();
				foreach ( $this->actions as $action ) {
					$action_slug = sanitize_title( $action );
					$action_label = ucwords( $action );
					if ( 'delete' == $action_slug ) {
						$actions[ $action_slug ] = sprintf( '<a href="?page=%s&doaction=%s&'.$this->primary_col.'=%s">'.$action_label.'</a>',$this->admin_listing_page_name,$action_slug,$item->{$this->primary_col} ); } else if ( 'edit' == $action_slug ) {
						$actions[ $action_slug ] = sprintf( '<a href="?page=%s&doaction=%s&'.$this->primary_col.'=%s">'.$action_label.'</a>',$this->admin_add_page_name,$action_slug,$item->{$this->primary_col} );
						} else { $actions[ $action_slug ] = sprintf( '<a href="?page=%s&doaction=%s&'.$this->primary_col.'=%s">'.$action_label.'</a>',$this->admin_listing_page_name,$action_slug,$item->{$this->primary_col} ); }
				}
				return sprintf( '%1$s %2$s', $item->{$this->col_showing_links}, $this->row_actions( $actions ) );

			} else {
				$return = $item->$column_name;
			}
			return $return;
		}

		/**
		 * Checkbox for each record.
		 * @param  array $item Record.
		 * @return string       Checkbox Element.
		 */
		function column_cb($item) {
			return sprintf( '<input type="checkbox" name="id[]" value="%s" />', $item->{$this->primary_col} ); }
		/**
		 * Sorting Order
		 * @param  string $a First element.
		 * @param  string $b Second element.
		 * @return string    Winner element.
		 */
		function usort_reorder( $a, $b ) {

			$orderby = ( ! empty( $_GET['orderby'] ) ) ? wp_unslash( $_GET['orderby'] ) : '';
			$order = ( ! empty( $_GET['order'] ) ) ? wp_unslash( $_GET['order'] ) : 'asc';
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' == $order ) ? $result : -$result;
		}
		/**
		 * Get bulk actions.
		 * @return array Bulk action listing.
		 */
		function get_bulk_actions() {

			$actions = array(
			'delete'    => 'Delete',
			);
			$actions = array_merge( $actions, (array) $this->bulk_actions );
			return $actions;
		}
		/**
		 * Get records from ids.
		 * @return array Records ID.
		 */
		function get_user_selected_records() {

			$ids = isset( $_REQUEST['id'] ) ? wp_unslash( $_REQUEST['id'] ) : array();
			if ( is_array( $ids ) ) { $ids = implode( ',', $ids ); }
			if ( ! empty( $ids ) ) {
				return $ids; }
		}
		/**
		 * Process bulk actions.
		 */
		function process_bulk_action() {

			global $wpdb;
			$this->now_action = $this->current_action();
			$ids = $this->get_user_selected_records();
			if ( 'delete' === $this->current_action() and ! empty( $ids ) ) {

				$query = "DELETE FROM {$this->table} WHERE {$this->primary_col} IN($ids)";
				$del = $wpdb->query( $query );
				$this->response['success'] = (strpos( $ids, ',' ) !== false) ?  __( "Selected {$this->plural_label} Deleted Successfully.", $this->textdomain ) : __( 'Selected '.ucwords( ucwords( $this->singular_label ) ).' Deleted Successfully.', $this->textdomain );

			} else if ( 'export_csv' === $this->current_action() ) {
				ob_clean();
				global $wpdb;
				$ids = $this->get_user_selected_records();
				$ids = ( ! empty( $ids )) ? " WHERE {$this->primary_col} IN($ids) " : '';
				$this->columns = apply_filters('fc_export_columns', $this->columns );
				if ( !in_array( $this->primary_col,$this->columns ) ) {
						$this->columns = array_merge( array($this->primary_col => 'ID'),$this->columns);
						//unset( $entry[ $this->primary_col ] );
				}	
				$columns = array_keys( $this->columns );
				
				$columns = (count( $columns ) == 0) ? $columns[0] : implode( ',',$columns );
				$query = (empty( $this->sql )) ? "SELECT $columns FROM ".$this->table.$ids." order by {$this->primary_col} desc" : $this->sql;
				$data = $wpdb->get_results( $query,ARRAY_A );
				$tablerecords = array();
				if ( ! empty( $this->sql ) ) {
					$col_key_value = array();
					foreach ( $data[0] as $key => $val ) {  // Make csv's first row column heading according to columns selected in custom sql.
						$col_key_value[ $key ] = $key;
					}
					$tablerecords[] = $col_key_value;
				} else {
					$tablerecords[] = $this->columns;        // Make csv's first row column heading according automatic detected columns.
				}

				foreach ( $data as $entry ) {
					if ( array_key_exists( $this->primary_col,$entry ) ) {
						//unset( $entry[ $this->primary_col ] );
					}					
					$entry = apply_filters('update_export_col_value', $entry );
					$tablerecords[] = $entry;
				}
				header( 'Content-Type: application/csv' );
				header( "Content-Disposition: attachment; filename=\"{$this->plural_label}-Records.csv\";" );
				header( 'Pragma: no-cache' );
				$fp = fopen( 'php://output', 'w' );
				foreach ( $tablerecords as $record ) {
					fputcsv( $fp, $record );
				}
				fclose( $fp );
				exit;

			}
		}
		/**
		 * Show notification message based on response.
		 * @param  array $response Response.
		 */
		public function show_notification($response) {

			if ( ! empty( $response['error'] ) ) {
				$this->show_message( $response['error'],true ); } else if ( ! empty( $response['success'] ) ) {
				$this->show_message( $response['success'] ); }

		}
		/**
		 * Message html element.
		 * @param  string  $message  Message.
		 * @param  boolean $errormsg Error or not.
		 * @return string           Message element.
		 */
		public function show_message($message, $errormsg = false) {

			if ( empty( $message ) ) {
				return; }
			if ( $errormsg ) {
				echo "<div class='alert alert-info'>{$message}</div>";
			} else { 		echo "<div class='fc-msg fc-success'>{$message}</div>"; }

		}
		/**
		 * Prepare records before print.
		 */
		function prepare_items() {

			global $wpdb;
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->process_bulk_action();
			// Check whether query must be build through table name or an sql is provided by developer.
			$query = (empty( $this->sql )) ? 'SELECT * FROM '.$this->table : $this->sql;
			if ( $this->admin_listing_page_name == @$_GET['page'] && '' != @$_REQUEST['s'] ) {

				$s = @$_REQUEST['s'];
				$first_column;
				$remaining_columns = array();
				$basic_search_query = '';
				foreach ( $this->columns as $column_name => $columnlabel ) {

					if ( "{$this->primary_col}" == $column_name ) {
						continue;
					} else {
						if ( empty( $first_column ) ) {
							$first_column = $column_name;
							$basic_search_query = " WHERE {$column_name} LIKE '%".$s."%'";
						} else {
							$remaining_columns[] = $column_name;
							if ( ! @in_array( $column_name,$this->searchExclude ) ) {
								$basic_search_query .= " or {$column_name} LIKE '%".$s."%'"; }
						}
					}
				}

				$query_to_run = $query.$basic_search_query;
				$query_to_run .= " order by {$this->primary_col} desc";

			} else if ( ! empty( $_GET['orderby'] ) and ! empty( $_GET['order'] ) ) {
				$orderby = ( ! empty( $_GET['orderby'] ) ) ? wp_unslash( $_GET['orderby'] ) : $this->primary_col;
				$order   = ( ! empty( $_GET['order'] ) ) ? wp_unslash( $_GET['order'] ) : 'asc';
				$query_to_run = $query;
				$query_to_run .= " order by {$orderby} {$order}";
			} else {
				$query_to_run = $query;
				if ( ! empty( $this->currenttimestamp_field ) ) {
					$query_to_run = $this->filter_query( $query_to_run ); }
				$query_to_run .= " order by {$this->primary_col} desc";
			}

			$this->data = $wpdb->get_results( $query_to_run );
			$current_page = $this->get_pagenum();
			$total_items = count( $this->data );
			if(is_array($this->data) and !empty($this->data)) {
				$this->found_data = @array_slice( $this->data,( ( $current_page -1 ) * $this->per_page ), $this->per_page );
			} else {
				$this->found_data = array();
			}
			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
			) );
			$this->items = $this->found_data;

		}
		/**
		 * Extra Filter selection box at the top of table.
		 * @param  string $which Top or Bottom.
		 */
		function extra_tablenav( $which ) {
			$text = ( 'top' == $which ) ? $this->toptext : $this->bottomtext;
			echo $text;
			if ( 'top' != $which ) {
				return; }

			if ( ! empty( $this->currenttimestamp_field ) ) {
				$filter_by_post = sanitize_text_field( wp_unslash( $_POST['filter_by'] ) );
			?>
			<div class="alignleft actions">
			Filter By:
			<select name="filter_by" style="float:none;">
			<option value="">Select Filter Criteria</option>
			<option value="today" <?php selected( $filter_by_post, 'today' ); ?>>Today</option>
		<option value="yesterday" <?php selected( $filter_by_post, 'yesterday' ); ?>>Yesterday</option>
		<option value="this_week" <?php selected( $filter_by_post, 'this_week' ); ?>>This Week</option>
		<option value="this_month" <?php selected( $filter_by_post, 'this_month' ); ?>>This Month</option>
		<option value="last_3_months" <?php selected( $filter_by_post, 'last_3_months' ); ?>>Last 3 Months</option>
		<option value="last_6_months" <?php selected( $filter_by_post, 'last_6_months' ); ?>>Last 6 Months</option>
		<option value="last_year" <?php selected( $filter_by_post, 'last_year' ); ?>>Last Year</option>
		<option value="custom" <?php selected( $filter_by_post, 'custom' ); ?>>Custom</option>
		</select>
		<?php
		if ( wp_unslash( $_POST['from_date'] ) || wp_unslash( $_POST['to_date'] ) ) {
			$display = 'inline';
		} else { $display = 'none'; }
		?>
		<div id="custom_filter" style="display:<?php echo $display; ?>;"> From
	<input type="text" class="wpgmp_datepicker" name="from_date" id="from_date" value="<?php echo sanitize_text_field( $_POST['from_date'] );  ?>">
	To
	<input type="text" class="wpgmp_datepicker" name="to_date" id="to_date" value="<?php echo sanitize_text_field( $_POST['to_date'] );  ?>">
	</div>

	<?php submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
	</div>
	<?php

			}
		}
		/**
		 * display Pagination
		 * @param  string $which Top or Bottom.
		 */
		protected function pagination( $which ) {

			if ( empty( $this->_pagination_args ) ) {
				return;
			}
			$total_items = $this->_pagination_args['total_items'];
			$total_pages = $this->_pagination_args['total_pages'];
			$infinite_scroll = false;
			if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
				$infinite_scroll = $this->_pagination_args['infinite_scroll'];
			}

			$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

			$current = $this->get_pagenum();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

			$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

			$page_links = array();

			$disable_first = $disable_last = '';
			if ( 1 == $current ) {
				$disable_first = ' disabled';
			}
			if ( $current == $total_pages ) {
				$disable_last = ' disabled';
			}
			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'first-page' . $disable_first,
				esc_attr__( 'Go to the first page' ),
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				'&laquo;'
			);

			if ( isset( $_REQUEST['s'] ) and ! empty( $_REQUEST['s'] ) ) {
				$current_url = add_query_arg( 's', $_REQUEST['s'], $current_url ); }

			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'prev-page' . $disable_first,
				esc_attr__( 'Go to the previous page' ),
				esc_url( add_query_arg( 'paged', max( 1, $current -1 ), $current_url ) ),
				'&lsaquo;'
			);

			if ( 'bottom' == $which ) {
				$html_current_page = $current;
			} else {
				$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' title='%s' type='text' name='paged' value='%s' size='%d' />",
					'<label for="current-page-selector" class="screen-reader-text">' . __( 'Select Page' ) . '</label>',
					esc_attr__( 'Current page' ),
					$current,
					strlen( $total_pages )
				);
			}
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'next-page' . $disable_last,
				esc_attr__( 'Go to the next page' ),
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				'&rsaquo;'
			);

			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'last-page' . $disable_last,
				esc_attr__( 'Go to the last page' ),
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				'&raquo;'
			);

			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

			if ( $total_pages ) {
				$page_class = $total_pages < 2 ? ' one-page' : '';
			} else {
				$page_class = ' no-pages';
			}
			$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

			echo $this->_pagination;
		}

		/**
		 * Build Filter query.
		 * @param  string $query SQL query.
		 * @return string        SQL query.
		 */
		private function filter_query( $query ) {

			$add_query = '';
			$filter_by = $_REQUEST['filter_by'];
			switch ( $filter_by ) {
				case 'today'  :
					$today = getdate();
					$add_query = sprintf( "AND( YEAR({$this->currenttimestamp_field}) = %s AND MONTH({$this->currenttimestamp_field}) = %s AND DAYOFMONTH({$this->currenttimestamp_field}) = %s)", $today['year'], $today['mon'], $today['mday'] );
			break;
				case 'yesterday'  :
					$yesterday = strtotime( date( 'Y-m-d', strtotime( ' - 1 day' ) ) );
					$add_query = sprintf( "AND( YEAR({$this->currenttimestamp_field}) = %s AND MONTH({$this->currenttimestamp_field}) = %s AND DAYOFMONTH({$this->currenttimestamp_field}) = %s)",  date( 'Y',$yesterday ), date( 'm',$yesterday ), date( 'd',$yesterday ) );
			break;
				case 'this_week'  :
					$week = date( 'W' );
					$year = date( 'Y' );
					$add_query = sprintf( "AND( YEAR({$this->currenttimestamp_field}) = %s AND WEEK({$this->currenttimestamp_field}, 1) = %s )",  $year, $week );
			break;
				case 'this_month':
					$month = date( 'm' );
					$add_query = sprintf( "AND( MONTH({$this->currenttimestamp_field}) = %s )",  $month );
			break;
				case 'last_3_months' :
					$start_date = date( 'Y-m-d h:i:s', mktime( 0, 0, 0, date( 'm' ) , date( 'd' ), date( 'Y' ) ) );
					$end_date = date( 'Y-m-d h:i:s', mktime( 0, 0, 0, date( 'm' ) - 3, date( 'd' ), date( 'Y' ) ) );
					$add_query = sprintf( "AND {$this->currenttimestamp_field} > '%s' AND {$this->currenttimestamp_field} <= '%s'", $end_date, $start_date );
			break;
				case 'last_6_months' :
					$start_date = date( 'Y-m-d h:i:s', mktime( 0, 0, 0, date( 'm' ) , date( 'd' ), date( 'Y' ) ) );
					$end_date = date( 'Y-m-d h:i:s', mktime( 0, 0, 0, date( 'm' ) - 6, date( 'd' ), date( 'Y' ) ) );
					$add_query = sprintf( "AND {$this->currenttimestamp_field} > '%s' AND {$this->currenttimestamp_field} <= '%s'", $end_date, $start_date );
			break;
				case 'last_year' :
					$year = date( 'Y' );
					$add_query = sprintf( "AND YEAR({$this->currenttimestamp_field}) = %s",  $year );
			break;
				default :
			break;

			}

			if ( isset( $_POST['from_date'] ) && ! empty( $_POST['from_date'] ) && isset( $_POST['to_date'] ) && ! empty( $_POST['to_date'] ) ) {
				$from_date = date( 'Y-m-d h:i:s', strtotime( $_POST['from_date'] ) );
				$to_date = date( 'Y-m-d h:i:s', strtotime( $_POST['to_date'] ) );
				$add_query = sprintf( "AND {$this->currenttimestamp_field} >= '%s' AND {$this->currenttimestamp_field} <= '%s'",   $from_date, $to_date );
			}

			if ( strpos( strtolower( $query ), 'where' ) == false ) {
				$query .= ' WHERE 1=1'; }
			if ( ! empty( $add_query ) ) {
				$query .= ' '.$add_query; }
			return $query;
		}

	}
}
