<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class AAL_Activity_Log_List_Table extends WP_List_Table {

	protected $_roles = array();

	protected $_caps = array();

	protected $_allow_caps = array();

	protected function _get_allow_caps() {
		if ( empty( $this->_allow_caps ) ) {
			$user = get_user_by( 'id', get_current_user_id() );
			if ( ! $user ) {
				wp_die( 'Not allowed here.' );
			}

			$user_cap   = strtolower( key( $user->caps ) );
			$allow_caps = array();

			foreach ( $this->_caps as $key => $cap_allow ) {
				if ( $key === $user_cap ) {
					$allow_caps = array_merge( $allow_caps, $cap_allow );

					break;
				}
			}

			// TODO: Find better way to Multisite compatibility.
			if ( is_super_admin() || current_user_can( 'view_all_aryo_activity_log' ) ) {
				$allow_caps = $this->_caps['administrator'];
			}

			if ( empty( $allow_caps ) ) {
				wp_die( 'Not allowed here.' );
			}

			$this->_allow_caps = array_unique( $allow_caps );
		}
		return $this->_allow_caps;
	}

	protected function _get_where_by_role() {
		$allow_modules = array();

		foreach ( $this->_roles as $key => $role ) {
			if ( current_user_can( $key ) || current_user_can( 'view_all_aryo_activity_log' ) ) {
				$allow_modules = array_merge( $allow_modules, $role );
			}
		}

		if ( empty( $allow_modules ) ) {
			wp_die( 'Not allowed here.' );
		}

		$allow_modules = array_unique( $allow_modules );

		$where = array();
		foreach ( $allow_modules as $type )
			$where[] .= '`object_type` = \'' . $type . '\'';

		$where_caps = array();
		foreach ( $this->_get_allow_caps() as $cap )
			$where_caps[] .= '`user_caps` = \'' . $cap . '\'';

		return 'AND (' . implode( ' OR ', $where ) . ') AND (' . implode( ' OR ', $where_caps ) . ')';
	}

	public function get_action_label( $action ) {
		return ucwords( str_replace( '_', ' ', __( $action, 'aryo-activity-log' ) ) );
	}

	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular'  => 'activity',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);

		$this->_roles = apply_filters(
			'aal_init_roles',
			array(
				// admin
				'manage_options' => array(
					'Core',
					'Export',
					'Posts',
					'Taxonomies',
					'Users',
					'Options',
					'Attachments',
					'Plugins',
					'Widgets',
					'Themes',
					'Menus',
					'Comments',

					// BC
					'Post',
					'Taxonomy',
					'User',
					'Plugin',
					'Widget',
					'Theme',
					'Menu',
				),
				// editor
				'edit_pages' => array(
					'Posts',
					'Taxonomies',
					'Attachments',
					'Comments',

					// BC
					'Post',
					'Taxonomy',
					'Attachment',
				),
			)
		);

		$default_rules = array(
			'administrator',
			'editor',
			'author',
			'guest',
		);

		global $wp_roles;

		$all_roles = array();
		foreach ( $wp_roles->roles as $key=>$wp_role ) {
			$all_roles[] = $key;
		}

		$this->_caps = apply_filters(
			'aal_init_caps',
			array(
				'administrator' => array_unique( array_merge( $default_rules, $all_roles ) ),
				'editor' => array( 'editor', 'author', 'guest' ),
				'author' => array( 'author', 'guest' ),
			)
		);

		add_screen_option(
			'per_page',
			array(
				'default' => 50,
				'label'   => __( 'Activities', 'aryo-activity-log' ),
				'option'  => 'edit_aal_logs_per_page',
			)
		);

		add_filter( 'set-screen-option', array( &$this, 'set_screen_option' ), 10, 3 );
		set_screen_options();
	}

	public function get_columns() {
		$columns = array(
			'date'        => __( 'Date', 'aryo-activity-log' ),
			'author'      => __( 'User', 'aryo-activity-log' ),
			'ip'          => __( 'IP', 'aryo-activity-log' ),
			'type'        => __( 'Topic', 'aryo-activity-log' ),
			'label'       => __( 'Context', 'aryo-activity-log' ),
			'description' => __( 'Meta', 'aryo-activity-log' ),
			'action'      => __( 'Action', 'aryo-activity-log' ),
		);

		return $columns;
	}

	public function get_sortable_columns() {
		return array(
			'ip' => 'hist_ip',
			'date' => array( 'hist_time', true ),
		);
	}

	public function column_default( $item, $column_name ) {
		$return = '';

		switch ( $column_name ) {
			case 'action':
				$return = '<a href="' . $this->get_filtered_link( 'showaction', $item->action ) . '">' . $this->get_action_label( $item->action ) . '</a>';
				break;

			case 'date':
				$return  = sprintf( '<strong>' . __( '%s ago', 'aryo-activity-log' ) . '</strong>', human_time_diff( $item->hist_time, current_time( 'timestamp' ) ) );

				$date_formatted = date( 'd/m/Y', $item->hist_time );
				$return .= '<br /><a href="' . $this->get_filtered_link( 'dateshow', $date_formatted ) . '">' . date( 'd/m/Y', $item->hist_time ) . '</a>';

				$return .= '<br />' . date( 'H:i:s', $item->hist_time );
				break;

			case 'ip':
				$return = '<a href="' . $this->get_filtered_link( 'filter_ip', $item->hist_ip ) . '">' . $item->hist_ip. '</a>';
				break;

			default:
				if ( isset( $item->$column_name ) ) {
					$return = $item->$column_name;
				}
		}

		$return = apply_filters( 'aal_table_list_column_default', $return, $item, $column_name );

		return $return;
	}

	public function column_author( $item ) {
		global $wp_roles;

		if ( ! empty( $item->user_id ) && 0 !== (int) $item->user_id ) {
			$user = get_user_by( 'id', $item->user_id );
			if ( $user instanceof WP_User && 0 !== $user->ID ) {
				return sprintf(
					'<a href="%s">%s <span class="aal-author-name">%s</span></a><br /><small>%s</small>',
					$this->get_filtered_link( 'usershow', $user->ID ),
					get_avatar( $user->ID, 40 ),
					$user->display_name,
					isset( $user->roles[0] ) && isset( $wp_roles->role_names[ $user->roles[0] ] ) ? $wp_roles->role_names[ $user->roles[0] ] : __( 'Unknown', 'aryo-activity-log' )
				);
			}
		}
		return sprintf(
			'<span class="aal-author-name">%s</span>',
			__( 'N/A', 'aryo-activity-log' )
		);
	}

	public function column_type( $item ) {
		$return = __( $item->object_type, 'aryo-activity-log' );

		if ( ! empty( $item->object_type ) ) {
			$link = $this->get_filtered_link( 'typeshow', $item->object_type );
			$return = "<a href=\"{$link}\">{$return}</a>";
		}

		$return = apply_filters( 'aal_table_list_column_type', $return, $item );
		return $return;
	}

	public function column_label( $item ) {
		$return = '';
		if ( ! empty( $item->object_subtype ) ) {
			$pt = get_post_type_object( $item->object_subtype );
			$return = ! empty( $pt->label ) ? $pt->label : $item->object_subtype;
		}

		$return = apply_filters( 'aal_table_list_column_label', $return, $item );
		return $return;
	}

	public function column_description( $item ) {
		$return = esc_html( $item->object_name );
		$actions = [];

		switch ( $item->object_type ) {
			case 'Post':
			case 'Posts':
				$actions = [
					'view' => sprintf( '<a href="%s">%s</a>', get_permalink( $item->object_id ), __( 'View', 'aryo-activity-log' ) ),
					'edit' => sprintf( '<a href="%s">%s</a>', get_edit_post_link( $item->object_id ), __( 'Edit', 'aryo-activity-log' ) ),
				];

				$return = esc_html( $item->object_name );
				break;

			case 'Taxonomy':
			case 'Taxonomies':
				if ( ! empty( $item->object_id ) ) {
					if ( is_taxonomy_viewable( $item->object_subtype ) ) {
						$term_view_link = get_term_link( absint( $item->object_id ), $item->object_subtype );

						if ( ! is_wp_error( $term_view_link ) ) {
							$actions['view'] = sprintf( '<a href="%s">%s</a>', $term_view_link, __( 'View', 'aryo-activity-log' ) );
						}
					}

					$term_edit_link = get_edit_term_link( $item->object_id, $item->object_subtype );
					if ( ! empty( $term_edit_link ) ) {
						$actions['edit'] = sprintf( '<a href="%s">%s</a>', $term_edit_link, __( 'Edit', 'aryo-activity-log' ) );
					}

					$return = esc_html( $item->object_name );
				}
				break;

			case 'Comments':
				if ( ! empty( $item->object_id ) && $comment = get_comment( $item->object_id ) ) {
					$actions['edit'] = sprintf( '<a href="%s">%s</a>', get_edit_comment_link( $item->object_id ), __( 'Edit', 'aryo-activity-log' ) );
				}

				$return = esc_html( "{$item->object_name} #{$item->object_id}" );
				break;

			case 'User':
			case 'Users':
				$user_edit_link = get_edit_user_link( $item->object_id );
				if ( ! empty( $user_edit_link ) ) {
					$actions['edit'] = sprintf( '<a href="%s">%s</a>', $user_edit_link, __( 'Edit', 'aryo-activity-log' ) );
				}

				if ( ! empty( $item->object_name ) ) {
					$return = __( 'Username:', 'aryo-activity-log' ) . ' ' . $item->object_name;
				}
				break;

			case 'Export':
				if ( 'all' === $item->object_name ) {
					$return = __( 'All', 'aryo-activity-log' );
				} else {
					$pt = get_post_type_object( $item->object_name );
					$return = ! empty( $pt->label ) ? $pt->label : $item->object_name;
				}
				break;

			case 'Options':
			case 'Core':
				$return = __( $item->object_name, 'aryo-activity-log' );
				break;
		}

		$return = apply_filters( 'aal_table_list_column_description', $return, $item );

		if ( ! empty( $actions ) ) {
			$i = 0;

			$return .= '<div class="row-actions">';
			foreach ( $actions as $action_name => $action_label ) {
				++$i;
				( 1 === $i ) ? $sep = '' : $sep = ' | ';
				$return .= "<span class=\"{$action_name}\">{$sep}{$action_label}</span>";
			}
			$return .= '</div>';
		}

		return $return;
	}

	public function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			$this->search_box( __( 'Search', 'aryo-activity-log' ), 'aal-search' );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	public function extra_tablenav_footer() {
		/**
		 * Filter list of record actions
		 *
		 * @return array Array items should represent action_id => 'Action Title'
		 */
		$actions = apply_filters( 'aal_record_actions', array() );
		?>
			<?php if ( count( $actions ) > 1 ) : ?>
			<div class="alignleft actions recordactions">
				<select name="aal-record-action">
					<option value=""><?php echo esc_attr__( 'Export File Format', 'aryo-activity-log' ); ?></option>
					<?php foreach ( $actions as $action_key => $action_title ) : ?>
					<option value="<?php echo esc_attr( $action_key ); ?>"><?php echo esc_html( $action_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php else :
				$action_title = reset( $actions );
				$action_key = key( $actions );
			?>
			<input type="hidden" name="aal-record-action" value="<?php echo esc_attr( $action_key ); ?>">
			<?php endif; ?>

			<button type="submit" name="aal-record-actions-submit" id="record-actions-submit" class="button button-primary" value="1">
				<?php
				// Is result filtering enabled?
				if ( array_key_exists( 'aal-filter', $_GET ) ) {
					echo sprintf( esc_html__( 'Export filtered records as %s', 'aryo-activity-log' ), $action_title );
				} else {
					echo sprintf( esc_html__( 'Export as %s', 'aryo-activity-log' ), $action_title );
				}
				?>
			</button>

			<?php wp_nonce_field( 'aal_actions_nonce', 'aal_actions_nonce' ); ?>
		<?php
	}

	public function extra_tablenav( $which ) {
		global $wpdb;

		if ( 'bottom' === $which ) {
			$this->extra_tablenav_footer();
		}

		if ( 'top' !== $which )
			return;

		echo '<div class="alignleft actions">';

		$users = $wpdb->get_results(
			'SELECT DISTINCT `user_id` FROM `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `user_id`
				ORDER BY `user_id`
				LIMIT 100
			;'
		);

		$types = $wpdb->get_results(
			'SELECT DISTINCT `object_type` FROM `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `object_type`
				ORDER BY `object_type`
			;'
		);

		// Make sure we get items for filter.
		if ( $users || $types ) {
			if ( ! isset( $_REQUEST['dateshow'] ) )
				$_REQUEST['dateshow'] = '';

			$date_options = array(
				'' => __( 'All Time', 'aryo-activity-log' ),
				'today' => __( 'Today', 'aryo-activity-log' ),
				'yesterday' => __( 'Yesterday', 'aryo-activity-log' ),
				'week' => __( 'Week', 'aryo-activity-log' ),
				'month' => __( 'Month', 'aryo-activity-log' ),
			);
			echo '<select name="dateshow" id="hs-filter-date">';
			foreach ( $date_options as $key => $value )
				printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['dateshow'], $key, false ), $value );
			echo '</select>';

			submit_button( __( 'Filter', 'aryo-activity-log' ), 'button', 'aal-filter', false, array( 'id' => 'activity-query-submit' ) );
		}

		if ( $users ) {
			if ( ! isset( $_REQUEST['capshow'] ) )
				$_REQUEST['capshow'] = '';

			$output = array();
			foreach ( $this->_get_allow_caps() as $cap ) {
				$output[ $cap ] = __( ucwords( $cap ), 'aryo-activity-log' );
			}

			if ( ! empty( $output ) ) {
				echo '<select name="capshow" id="hs-filter-capshow">';
				printf( '<option value="">%s</option>', __( 'All Roles', 'aryo-activity-log' ) );
				foreach ( $output as $key => $value ) {
					printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['capshow'], $key, false ), $value );
				}
				echo '</select>';
			}

			if ( ! isset( $_REQUEST['usershow'] ) )
				$_REQUEST['usershow'] = '';

			$output = array();
			foreach ( $users as $_user ) {
				if ( 0 === (int) $_user->user_id ) {
					$output[0] = __( 'N/A', 'aryo-activity-log' );
					continue;
				}

				$user = get_user_by( 'id', $_user->user_id );
				if ( $user )
					$output[ $user->ID ] = $user->user_nicename;
			}

			if ( ! empty( $output ) ) {
				echo '<select name="usershow" id="hs-filter-usershow">';
				printf( '<option value="">%s</option>', __( 'All Users', 'aryo-activity-log' ) );
				foreach ( $output as $key => $value ) {
					printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['usershow'], $key, false ), $value );
				}
				echo '</select>';
			}
		}

		if ( $types ) {
			if ( ! isset( $_REQUEST['typeshow'] ) )
				$_REQUEST['typeshow'] = '';

			$output = array();
			foreach ( $types as $type )
				$output[] = sprintf( '<option value="%s"%s>%s</option>', $type->object_type, selected( $_REQUEST['typeshow'], $type->object_type, false ), __( $type->object_type, 'aryo-activity-log' ) );

			echo '<select name="typeshow" id="hs-filter-typeshow">';
			printf( '<option value="">%s</option>', __( 'All Topics', 'aryo-activity-log' ) );
			echo implode( '', $output );
			echo '</select>';
		}

		$actions = $wpdb->get_results(
			'SELECT DISTINCT `action` FROM  `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `action`
				ORDER BY `action`
			;'
		);

		if ( $actions ) {
			if ( ! isset( $_REQUEST['showaction'] ) )
				$_REQUEST['showaction'] = '';

			$output = array();
			foreach ( $actions as $action )
				$output[] = sprintf( '<option value="%s"%s>%s</option>', $action->action, selected( $_REQUEST['showaction'], $action->action, false ), $this->get_action_label( $action->action ) );

			echo '<select name="showaction" id="hs-filter-showaction">';
			printf( '<option value="">%s</option>', __( 'All Actions', 'aryo-activity-log' ) );
			echo implode( '', $output );
			echo '</select>';
		}

		$filters = array(
			'dateshow',
			'capshow',
			'usershow',
			'typeshow',
			'showaction',
		);

		foreach ( $filters as $filter ) {
			if ( ! empty( $_REQUEST[ $filter ] ) ) {
				echo '<a href="' . $this->get_filtered_link() . '" id="aal-reset-filter"><span class="dashicons dashicons-dismiss"></span>' . __( 'Reset Filters', 'aryo-activity-log' ) . '</a>';
				break;
			}
		}

		echo '</div>';
	}

	public function prepare_items() {
		global $wpdb;

		$items_per_page        = $this->get_items_per_page( 'edit_aal_logs_per_page', 20 );
		$this->_column_headers = array( $this->get_columns(), get_hidden_columns( $this->screen ), $this->get_sortable_columns() );
		$where                 = ' WHERE 1 = 1';

		if ( ! isset( $_REQUEST['order'] ) || ! in_array( $_REQUEST['order'], array( 'desc', 'asc' ) ) ) {
			$_REQUEST['order'] = 'DESC';
		}
		if ( ! isset( $_REQUEST['orderby'] ) || ! in_array( $_REQUEST['orderby'], array( 'hist_time', 'hist_ip' ) ) ) {
			$_REQUEST['orderby'] = 'hist_time';
		}

		if ( ! empty( $_REQUEST['typeshow'] ) ) {
			$where .= $wpdb->prepare( ' AND `object_type` = %s', $_REQUEST['typeshow'] );
		}

		if ( isset( $_REQUEST['showaction'] ) && '' !== $_REQUEST['showaction'] ) {
			$where .= $wpdb->prepare( ' AND `action` = %s', $_REQUEST['showaction'] );
		}

		if ( isset( $_REQUEST['filter_ip'] ) && '' !== $_REQUEST['filter_ip'] ) {
			$where .= $wpdb->prepare( ' AND `hist_ip` = %s', $_REQUEST['filter_ip'] );
		}

		if ( isset( $_REQUEST['usershow'] ) && '' !== $_REQUEST['usershow'] ) {
			$where .= $wpdb->prepare( ' AND `user_id` = %d', $_REQUEST['usershow'] );
		}

		if ( isset( $_REQUEST['capshow'] ) && '' !== $_REQUEST['capshow'] ) {
			$where .= $wpdb->prepare( ' AND `user_caps` = %s', strtolower( $_REQUEST['capshow'] ) );
		}

		if ( isset( $_REQUEST['dateshow'] ) ) {
			$current_time = current_time( 'timestamp' );

			if ( in_array( $_REQUEST['dateshow'], array( 'today', 'yesterday', 'week', 'month' ) ) ) {
				// Today
				$start_time = mktime( 0, 0, 0, date( 'm', $current_time ), date( 'd', $current_time ), date( 'Y', $current_time ) );
				$end_time = mktime( 23, 59, 59, date( 'm', $current_time ), date( 'd', $current_time ), date( 'Y', $current_time ) );

				if ( 'yesterday' === $_REQUEST['dateshow'] ) {
					$start_time = strtotime( 'yesterday', $start_time );
					$end_time = mktime( 23, 59, 59, date( 'm', $start_time ), date( 'd', $start_time ), date( 'Y', $start_time ) );
				} elseif ( 'week' === $_REQUEST['dateshow'] ) {
					$start_time = strtotime( '-1 week', $start_time );
				} elseif ( 'month' === $_REQUEST['dateshow'] ) {
					$start_time = strtotime( '-1 month', $start_time );
				}
			} else {
				$date_array = explode( '/', $_REQUEST['dateshow'] );

				if ( 3 === count( $date_array ) ) {
					$start_time = mktime( 0, 0, 0, (int) $date_array[1], (int) $date_array[0], (int) $date_array[2] );
					$end_time = mktime( 23, 59, 59, (int) $date_array[1], (int) $date_array[0], (int) $date_array[2] );
				}
			}

			if ( ! empty( $start_time ) && ! empty( $end_time ) ) {
				$where .= $wpdb->prepare( ' AND `hist_time` > %d AND `hist_time` < %d', $start_time, $end_time );
			}
		}

		if ( isset( $_REQUEST['s'] ) ) {
			// Search only searches 'description' fields.
			$where .= $wpdb->prepare( ' AND `object_name` LIKE %s', '%' . $wpdb->esc_like( $_REQUEST['s'] ) . '%' );
		}

		$offset = ( $this->get_pagenum() - 1 ) * $items_per_page;


		$total_items = $wpdb->get_var(
			'SELECT COUNT(`histid`) FROM  `' . $wpdb->activity_log . '`
				' . $where . '
					' . $this->_get_where_by_role()
		);

		$items_orderby = sanitize_sql_orderby( filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING ) );
		if ( empty( $items_orderby ) ) {
			$items_orderby = 'hist_time'; // Sort by time by default.
		}

		$items_order = strtoupper( $_REQUEST['order'] );
		if ( empty( $items_order ) || ! in_array( $items_order, array( 'DESC', 'ASC' ) ) ) {
			$items_order = 'DESC'; // Descending order by default.
		}

		$this->items = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM `' . $wpdb->activity_log . '`
				' . $where . '
					' . $this->_get_where_by_role() . '
					ORDER BY ' . $items_orderby . ' ' . $items_order . '
					LIMIT %d, %d;',
			$offset,
			$items_per_page
		) );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $items_per_page,
			'total_pages' => ceil( $total_items / $items_per_page ),
		) );
	}

	public function set_screen_option( $status, $option, $value ) {
		if ( 'edit_aal_logs_per_page' === $option )
			return $value;
		return $status;
	}

	public function search_box( $text, $input_id ) {
		$search_data = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

		$input_id = $input_id . '-search-input';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php echo esc_attr( $search_data ); ?>" />
			<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
		</p>
	<?php
	}

	private function get_filtered_link( $name = '', $value = '' ) {
		$base_page_url = menu_page_url( 'activity_log_page', false );

		if ( empty( $name ) ) {
			return $base_page_url;
		}

		return add_query_arg( $name, $value, $base_page_url );
	}
}
