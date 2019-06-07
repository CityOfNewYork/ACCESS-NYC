<?php

namespace BulkWP\BulkDelete\Core\Base\Mixin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Container of all Render methods.
 *
 * Ideally this should be a Trait. Since Bulk Delete still supports PHP 5.3, this is implemented as a class.
 * Once the minimum requirement is increased to PHP 5.3, this will be changed into a Trait.
 *
 * @since 6.0.0
 */
abstract class Renderer extends Fetcher {
	/**
	 * Slug for the form fields.
	 *
	 * @var string
	 */
	protected $field_slug;

	/**
	 * Render post status including custom post status.
	 *
	 * @param string $post_type The post type for which the post status should be displayed.
	 */
	protected function render_post_status( $post_type = 'post' ) {
		$post_statuses = $this->get_post_statuses();
		$post_count    = wp_count_posts( $post_type );

		foreach ( $post_statuses as $post_status ) : ?>
			<tr>
				<td>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" id="smbd_<?php echo esc_attr( $post_status->name ); ?>"
						value="<?php echo esc_attr( $post_status->name ); ?>" type="checkbox">

					<label for="smbd_<?php echo esc_attr( $post_status->name ); ?>">
						<?php echo esc_html( $post_status->label ), ' '; ?>
						<?php if ( property_exists( $post_count, $post_status->name ) ) : ?>
							(<?php echo absint( $post_count->{ $post_status->name } ) . ' ', __( 'Posts', 'bulk-delete' ); ?>)
						<?php endif; ?>
					</label>
				</td>
			</tr>
		<?php endforeach;
	}

	/**
	 * Render Post Types as radio buttons.
	 */
	protected function render_post_type_as_radios() {
		$post_types = $this->get_post_types();
		?>

		<?php foreach ( $post_types as $post_type ) : ?>

			<tr>
				<td scope="row">
					<input type="radio" name="<?php echo esc_attr( $this->field_slug ); ?>_post_type"
						value="<?php echo esc_attr( $post_type->name ); ?>"
						id="smbd_post_type_<?php echo esc_html( $post_type->name ); ?>">

					<label for="smbd_post_type_<?php echo esc_html( $post_type->name ); ?>">
						<?php echo esc_html( $post_type->label ); ?>
					</label>
				</td>
			</tr>

		<?php endforeach; ?>
		<?php
	}

	/**
	 * Render Post type with status and post count checkboxes.
	 *
	 * @since 6.0.1 Added $multiple param.
	 *
	 * @param bool $multiple_select Whether multiple select should be supported. Default true.
	 */
	protected function render_post_type_with_status( $multiple_select = true ) {
		$post_types_by_status = $this->get_post_types_by_status();

		$name = 'smbd_' . $this->field_slug;
		if ( $multiple_select ) {
			$name .= '[]';
		}
		?>

		<tr>
			<td scope="row" colspan="2">
				<select data-placeholder="<?php esc_attr_e( 'Select Post Type', 'bulk-delete' ); ?>"
					name="<?php echo esc_attr( $name ); ?>" class="enhanced-post-types-with-status"
					<?php if ( $multiple_select ) : ?>
						multiple
					<?php endif; ?>
				>

				<?php foreach ( $post_types_by_status as $post_type => $all_status ) : ?>
					<optgroup label="<?php echo esc_html( $post_type ); ?>">

					<?php foreach ( $all_status as $status_key => $status_value ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>">
							<?php echo esc_html( $status_value ); ?>
						</option>
					<?php endforeach; ?>

					</optgroup>
				<?php endforeach; ?>

				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Split post type and status.
	 *
	 * @param string $str Post type and status combination.
	 *
	 * @return array Post type and status as elements of array.
	 */
	protected function split_post_type_and_status( $str ) {
		$type_status = array();

		if ( strpos( $str, '|' ) === false ) {
			$str_arr = explode( '-', $str );
		} else {
			$str_arr = explode( '|', $str );
		}

		if ( count( $str_arr ) > 1 ) {
			$type_status['status'] = end( $str_arr );
			$type_status['type']   = implode( '-', array_slice( $str_arr, 0, - 1 ) );
		} else {
			$type_status['status'] = 'publish';
			$type_status['type']   = $str;
		}

		return $type_status;
	}

	/**
	 * Render post reassign settings.
	 */
	protected function render_post_reassign_settings() {
		?>
		<tr>
			<td scope="row" colspan="2">
				<label><input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_post_reassign" value="false" type="radio"
					checked="checked" class="post-reassign"> <?php _e( 'Also delete all posts of the users', 'bulk-delete' ); ?></label>
				<label><input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_post_reassign" value="true" type="radio"
					id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_post_reassign" class="post-reassign"> <?php _e( 'Re-assign the posts to', 'bulk-delete' ); ?></label>
				<?php
				wp_dropdown_users(
					array(
						'name'             => 'smbd_' . esc_attr( $this->field_slug ) . '_reassign_user_id',
						'class'            => 'reassign-user',
						'show_option_none' => __( 'Select User', 'bulk-delete' ),
					)
				);
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render user role dropdown.
	 *
	 * @param bool $show_users_with_no_roles Should users with no user roles be shown? Default false.
	 */
	protected function render_user_role_dropdown( $show_users_with_no_roles = false ) {
		$roles       = get_editable_roles();
		$users_count = count_users();
		?>

		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_roles[]" class="enhanced-role-dropdown"
				multiple="multiple" data-placeholder="<?php _e( 'Select User Role', 'bulk-delete' ); ?>">

			<?php foreach ( $roles as $role => $role_details ) : ?>
				<option value="<?php echo esc_attr( $role ); ?>">
					<?php echo esc_html( $role_details['name'] ), ' (', absint( $this->get_user_count_by_role( $role, $users_count ) ), ' ', __( 'Users', 'bulk-delete' ), ')'; ?>
				</option>
			<?php endforeach; ?>

			<?php if ( $show_users_with_no_roles ) : ?>
				<?php if ( isset( $users_count['avail_roles']['none'] ) && $users_count['avail_roles']['none'] > 0 ) : ?>
					<option value="none">
						<?php echo __( 'No role', 'bulk-delete' ), ' (', absint( $users_count['avail_roles']['none'] ), ' ', __( 'Users', 'bulk-delete' ), ')'; ?>
					</option>
				<?php endif; ?>
			<?php endif; ?>
		</select>

		<?php
	}

	/**
	 * Render Post type dropdown.
	 */
	protected function render_post_type_dropdown() {
		bd_render_post_type_dropdown( $this->field_slug );
	}

	/**
	 * Render Taxonomy dropdown.
	 */
	protected function render_taxonomy_dropdown() {
		$builtin_taxonomies = get_taxonomies( array( '_builtin' => true ), 'objects' );
		$custom_taxonomies  = get_taxonomies( array( '_builtin' => false ), 'objects' );
		?>
			<select class="enhanced-dropdown" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_taxonomy">
				<optgroup label="<?php esc_attr_e( 'Built-in Taxonomies', 'bulk-delete' ); ?>">
					<?php foreach ( $builtin_taxonomies as $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>">
							<?php echo esc_html( $taxonomy->label . ' (' . $taxonomy->name . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>

				<optgroup label="<?php esc_attr_e( 'Custom Taxonomies', 'bulk-delete' ); ?>">
					<?php foreach ( $custom_taxonomies as $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>">
							<?php echo esc_html( $taxonomy->label . ' (' . $taxonomy->name . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			</select>
		<?php
	}

	/**
	 * Render Category dropdown.
	 */
	protected function render_category_dropdown() {
		$categories = $this->get_categories();
		?>

		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_category[]" data-placeholder="<?php _e( 'Select Categories', 'bulk-delete' ); ?>"
				class="<?php echo sanitize_html_class( $this->enable_ajax_if_needed_to_dropdown_class_name( count( $categories ), 'select2-taxonomy' ) ); ?>"
				data-taxonomy="category" multiple>

			<option value="all">
				<?php _e( 'All Categories', 'bulk-delete' ); ?>
			</option>

			<?php foreach ( $categories as $category ) : ?>
				<option value="<?php echo absint( $category->cat_ID ); ?>">
					<?php echo esc_html( $category->cat_name ), ' (', absint( $category->count ), ' ', __( 'Posts', 'bulk-delete' ), ')'; ?>
				</option>
			<?php endforeach; ?>

		</select>
		<?php
	}

	/**
	 * Render String based comparison operators dropdown.
	 */
	protected function render_string_comparison_operators() {
		?>
		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_operator">
			<option value="equal_to"><?php _e( 'equal to', 'bulk-delete' ); ?></option>
			<option value="not_equal_to"><?php _e( 'not equal to', 'bulk-delete' ); ?></option>
			<option value="starts_with"><?php _e( 'starts with', 'bulk-delete' ); ?></option>
			<option value="ends_with"><?php _e( 'ends with', 'bulk-delete' ); ?></option>
			<option value="contains"><?php _e( 'contains', 'bulk-delete' ); ?></option>
			<option value="not_contains"><?php _e( 'not contains', 'bulk-delete' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render number based comparison operators dropdown.
	 */
	protected function render_number_comparison_operators() {
		?>
		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_operator">
			<option value="="><?php _e( 'equal to', 'bulk-delete' ); ?></option>
			<option value="!="><?php _e( 'not equal to', 'bulk-delete' ); ?></option>
			<option value="<"><?php _e( 'less than', 'bulk-delete' ); ?></option>
			<option value=">"><?php _e( 'greater than', 'bulk-delete' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render data types dropdown.
	 */
	protected function render_data_types_dropdown() {
		?>
		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_type" class="meta-type">
			<option value="numeric"><?php _e( 'Number', 'bulk-delete' ); ?></option>
			<option value="string"><?php _e( 'Character', 'bulk-delete' ); ?></option>
			<option value="date"><?php _e( 'Date', 'bulk-delete' ); ?></option>
		</select>
		<?php
	}
	/**
	 * Render numeric comparison operators dropdown.
	 *
	 * @param string $class     Class to be applied.
	 * @param array  $operators List of Operators needed.
	 */
	protected function render_numeric_operators_dropdown( $class = 'numeric', $operators = array( 'all' ) ) {
		$all_numeric_operators = array(
			'='           => 'equal to',
			'!='          => 'not equal to',
			'<'           => 'less than',
			'<='          => 'less than or equal to',
			'>'           => 'greater than',
			'>='          => 'greater than or equal to',
			'IN'          => 'in',
			'NOT IN'      => 'not in',
			'BETWEEN'     => 'between',
			'NOT BETWEEN' => 'not between',
			'EXISTS'      => 'exists',
			'NOT EXISTS'  => 'not exists',
		);
		if ( in_array( 'all', $operators, true ) ) {
			$operators = array_keys( $all_numeric_operators );
		}
		?>
		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_operator" class= "<?php echo esc_attr( $class ); ?>">
		<?php
		foreach ( $operators as $operator ) {
			echo '<option value="' . $operator . '">' . __( $all_numeric_operators[ $operator ], 'bulk-delete' ) . '</option>';
		}
		?>
		</select>
		<?php
	}
	/**
	 * Render string comparison operators dropdown.
	 *
	 * @param string $class     Class to be applied.
	 * @param array  $operators List of Operators needed.
	 */
	protected function render_string_operators_dropdown( $class = 'string', $operators = array( 'all' ) ) {
		// STARTS_WITH and ENDS_WITH operators needs a handler as SQL does not support these operators in queries.
		$all_string_operators = array(
			'='           => 'equal to',
			'!='          => 'not equal to',
			'IN'          => 'in',
			'NOT IN'      => 'not in',
			'LIKE'        => 'contains',
			'NOT LIKE'    => 'not contains',
			'EXISTS'      => 'exists',
			'NOT EXISTS'  => 'not exists',
			'STARTS_WITH' => 'starts with',
			'ENDS_WITH'   => 'ends with',
		);
		if ( in_array( 'all', $operators, true ) ) {
			$operators = array_keys( $all_string_operators );
		}
		?>
		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_operator" class="<?php echo esc_attr( $class ); ?>">
		<?php
		foreach ( $operators as $operator ) {
			echo '<option value="' . $operator . '">' . __( $all_string_operators[ $operator ], 'bulk-delete' ) . '</option>';
		}
		?>
		</select>
		<?php
	}

	/**
	 * Render Tags dropdown.
	 */
	protected function render_tags_dropdown() {
		$tags = $this->get_tags();
		?>

		<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" data-placeholder="<?php _e( 'Select Tags', 'bulk-delete' ); ?>"
				class="<?php echo sanitize_html_class( $this->enable_ajax_if_needed_to_dropdown_class_name( count( $tags ), 'select2-taxonomy' ) ); ?>"
				data-taxonomy="post_tag" multiple>

			<option value="all">
				<?php _e( 'All Tags', 'bulk-delete' ); ?>
			</option>

			<?php foreach ( $tags as $tag ) : ?>
				<option value="<?php echo absint( $tag->term_id ); ?>">
					<?php echo esc_html( $tag->name ), ' (', absint( $tag->count ), ' ', __( 'Posts', 'bulk-delete' ), ')'; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Get the class name for select2 dropdown based on the number of items present.
	 *
	 * @param int    $count      The number of items present.
	 * @param string $class_name Primary class name.
	 *
	 * @return string Class name.
	 */
	protected function enable_ajax_if_needed_to_dropdown_class_name( $count, $class_name ) {
		if ( $count >= $this->get_enhanced_select_threshold() ) {
			$class_name .= '-ajax';
		}

		return $class_name;
	}

	/**
	 * Render Sticky Posts dropdown.
	 */
	protected function render_sticky_posts_dropdown() {
		$sticky_posts = $this->get_sticky_posts();
		?>

		<table class="optiontable">
			<?php if ( count( $sticky_posts ) > 1 ) : ?>
				<tr>
					<td scope="row">
						<label>
							<input type="checkbox" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" value="all">
							<?php echo __( 'All sticky posts', 'bulk-delete' ), ' (', count( $sticky_posts ), ' ', __( 'Posts', 'bulk-delete' ), ')'; ?>
						</label>
					</td>
				</tr>
			<?php endif; ?>

			<?php foreach ( $sticky_posts as $post ) : ?>
				<?php $author = get_userdata( $post->post_author ); ?>
				<tr>
					<td scope="row">
						<label>
							<input type="checkbox" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>[]" value="<?php echo absint( $post->ID ); ?>">
							<?php
								echo esc_html( $post->post_title ), ' - ',
									__( 'Published on', 'bulk-delete' ), ' ', get_the_date( get_option( 'date_format' ), $post->ID ),
									__( ' by ', 'bulk-delete' ), esc_html( $author->display_name );
							?>
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Renders exclude sticky posts checkbox.
	 */
	protected function render_exclude_sticky_settings() {
		if ( $this->are_sticky_posts_present() ) : // phpcs:ignore?>
		<tr>
			<td scope="row">
				<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_exclude_sticky" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_exclude_sticky" value="true" type="checkbox">
			</td>
			<td>
				<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_exclude_sticky"><?php _e( 'Exclude sticky posts', 'bulk-delete' ); ?></label>
			</td>
		</tr>
		<?php endif; // phpcs:ignore?>
		<?php
	}

	/**
	 * Render Post Types as checkboxes.
	 *
	 * @since 5.6.0
	 *
	 * @param string $name Name of post type checkboxes.
	 */
	protected function render_post_type_checkboxes( $name ) {
		$post_types = bd_get_post_types();
		?>

		<?php foreach ( $post_types as $post_type ) : ?>

		<tr>
			<td scope="row">
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $post_type->name ); ?>"
					id="smbd_post_type_<?php echo esc_html( $post_type->name ); ?>" checked>

				<label for="smbd_post_type_<?php echo esc_html( $post_type->name ); ?>">
					<?php echo esc_html( $post_type->label ); ?>
				</label>
			</td>
		</tr>

		<?php endforeach; ?>
		<?php
	}

	/**
	 * Render the "private post" setting fields.
	 */
	protected function render_private_post_settings() {
		bd_render_private_post_settings( $this->field_slug );
	}

	/**
	 * Render sticky settings.
	 */
	protected function render_sticky_action_settings() {
		?>
		<tr>
			<td scope="row" colspan="2">
				<label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_sticky_action" value="unsticky" type="radio" checked>
					<?php _e( 'Remove Sticky', 'bulk-delete' ); ?>
				</label>
				<label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_sticky_action" value="delete" type="radio">
					<?php _e( 'Delete Post', 'bulk-delete' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render filtering table header.
	 */
	protected function render_filtering_table_header() {
		bd_render_filtering_table_header();
	}

	/**
	 * Render restrict settings.
	 */
	protected function render_restrict_settings() {
		bd_render_restrict_settings( $this->field_slug, $this->item_type );
	}

	/**
	 * Render delete settings.
	 */
	protected function render_delete_settings() {
		bd_render_delete_settings( $this->field_slug );
		/**
		 * This action is primarily for adding delete attachment settings.
		 *
		 * @since 6.0.0
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseModule The delete module.
		 */
		do_action( 'bd_render_attachment_settings', $this );
	}

	/**
	 * Render limit settings.
	 *
	 * @param string $item_type Item Type to be displayed in label.
	 */
	protected function render_limit_settings( $item_type = '' ) {
		if ( empty( $item_type ) ) {
			$item_type = $this->item_type;
		}
		bd_render_limit_settings( $this->field_slug, $item_type );
	}

	/**
	 * Render cron settings based on whether scheduler is present or not.
	 */
	protected function render_cron_settings() {
		$pro_class = '';

		$disabled_attr = 'disabled';
		if ( empty( $this->scheduler_url ) ) {
			$disabled_attr = '';
		}
		?>

		<tr>
			<td scope="row" colspan="2">
				<label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron" value="false" type="radio"
					checked="checked" class="schedule-deletion">
					<?php _e( 'Delete now', 'bulk-delete' ); ?>
				</label>

				<label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron" value="true" type="radio"
					class="schedule-deletion" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron" <?php echo esc_attr( $disabled_attr ); ?>>
					<?php _e( 'Schedule', 'bulk-delete' ); ?>
				</label>

				<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron_start"
					id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron_start" value="now"
					type="text" <?php echo esc_attr( $disabled_attr ); ?> autocomplete="off"><?php _e( 'repeat ', 'bulk-delete' ); ?>

				<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron_freq"
						id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_cron_freq" <?php echo esc_attr( $disabled_attr ); ?>>

					<option value="-1"><?php _e( "Don't repeat", 'bulk-delete' ); ?></option>
					<?php
					/**
					 * List of cron schedules.
					 *
					 * @since 6.0.0
					 *
					 * @param array                                   $cron_schedules List of cron schedules.
					 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module         Module.
					 */
					$cron_schedules = apply_filters( 'bd_cron_schedules', wp_get_schedules(), $this );
					?>

					<?php foreach ( $cron_schedules as $key => $value ) : ?>
						<option
							value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['display'] ); ?></option>
					<?php endforeach; ?>
				</select>

				<?php if ( ! empty( $this->scheduler_url ) ) : ?>
					<?php
					$pro_class = 'bd-' . str_replace( '_', '-', $this->field_slug ) . '-pro';

					/**
					 * HTML class of the span that displays the 'Pro only feature' message.
					 *
					 * @since 6.0.0
					 *
					 * @param string                                  $pro_class  HTML class.
					 * @param string                                  $field_slug Field Slug of module.
					 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module     Module.
					 */
					$pro_class = apply_filters( 'bd_pro_only_feature_class', $pro_class, $this->field_slug, $this )
					?>

					<span class="<?php echo sanitize_html_class( $pro_class ); ?>" style="color:red">
						<?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a
							href="<?php echo esc_url( $this->scheduler_url ); ?>" target="_blank">Buy now</a>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<tr
		<?php if ( ! empty( $pro_class ) ) : ?>
			class="<?php echo sanitize_html_class( $pro_class ); ?>" style="display: none;"
		<?php endif; ?>
		>

			<td scope="row" colspan="2">
				<?php
				_e( 'Enter time in <strong>Y-m-d H:i:s</strong> format or enter <strong>now</strong> to use current time.', 'bulk-delete' );

				$markup = __( 'Want to add new a Cron schedule?', 'bulk-delete' ) . '&nbsp' .
					'<a href="https://bulkwp.com/docs/add-a-new-cron-schedule/?utm_campaign=Docs&utm_medium=wpadmin&utm_source=tooltip&utm_content=cron-schedule" target="_blank" rel="noopener">' . __( 'Find out how', 'bulk-delete' ) . '</a>';

				$content = __( 'Learn how to add your desired Cron schedule.', 'bulk-delete' );
				echo '&nbsp', bd_generate_help_tooltip( $markup, $content );
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render submit button.
	 */
	protected function render_submit_button() {
		bd_render_submit_button( $this->action );
	}
}
