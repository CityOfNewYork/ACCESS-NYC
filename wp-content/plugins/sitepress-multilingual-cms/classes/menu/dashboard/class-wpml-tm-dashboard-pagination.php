<?php

/**
 * Class WPML_TM_Dashboard_Pagination
 */
class WPML_TM_Dashboard_Pagination {

	private $post_limit_number = 0;

	public function add_hooks() {
		add_action( 'wpml_tm_dashboard_pagination', array( $this, 'add_tm_dashboard_pagination' ), 10, 2 );
		add_filter( 'wpml_tm_dashboard_post_query_args', array( $this, 'filter_dashboard_post_query_args_for_pagination' ), 10, 2 );
	}

	public function filter_dashboard_post_query_args_for_pagination( $query_args, $args ) {
		if ( ! empty( $args['type'] ) ) {
			unset( $query_args['no_found_rows'] );
		}
	}

	/**
	 * Sets value for posts limit query to be used in post_limits filter
	 *
	 * @param int $value
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-616
	 */
	public function setPostsLimitValue( $value ) {
		$this->post_limit_number = ( is_int( $value ) && $value > 0 ) ? $value : $this->post_limit_number;
	}

	/**
	 * Resets value of posts limit variable.
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-616
	 */
	public function resetPostsLimitValue() {
		$this->post_limit_number = 0;
	}

	/**
	 * Custom callback that's hooked into 'post_limits' filter to set custom limit of retrieved posts.
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-616
	 *
	 * @return string
	 */
	public function getPostsLimitQueryValue() {
		return ( 0 === $this->post_limit_number ) ? '' : 'LIMIT ' . $this->post_limit_number;
	}


	/**
	 * @param integer $posts_per_page
	 * @param integer $found_documents
	 */
	public function add_tm_dashboard_pagination( $posts_per_page, $found_documents ) {
		$found_documents = $found_documents;
		$total_pages     = ceil( $found_documents / $posts_per_page );
		$paged           = array_key_exists( 'paged', $_GET ) ? filter_var( $_GET['paged'], FILTER_SANITIZE_NUMBER_INT ) : false;
		$paged           = $paged ? $paged : 1;
		$page_links      = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => (int) $total_pages,
				'current'   => (int) $paged,
			)
		);
		if ( $page_links ) {
			?>
			<div class="tablenav-pages">
				<?php
				$page_from  = number_format_i18n( ( $paged - 1 ) * $posts_per_page + 1 );
				$page_to    = number_format_i18n( min( $paged * $posts_per_page, $found_documents ) );
				$page_total = number_format_i18n( $found_documents );
				?>
				<span class="displaying-num">
					<?php echo sprintf( esc_html__( 'Displaying %1$s&#8211;%2$s of %3$s', 'wpml-translation-management' ), $page_from, $page_to, $page_total ); ?>
				</span>
				<?php echo $page_links; ?>
			</div>
			<?php
		}
	}
}
