<?php
/**
 * /premium/templates/relevanssi-related.php
 *
 * Template for printing out the related posts.
 *
 * Make sure this template does not overwrite $post_id. Also note that the
 * template will be cached, so for example don't do separate code for mobile
 * and desktop users, because the caching won't care about whether the user
 * is a mobile or a desktop user and will randomly provide the wrong version to
 * users.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

$style = get_option( 'relevanssi_related_style' );

if ( ! empty( $related_posts ) ) :
	?>
	<div id="relevanssi_related">
		<div class="relevanssi_related_grid_header">
			<h3><?php esc_html_e( 'Related Posts', 'relevanssi' ); ?></h3>
		</div>
		<div id="relevanssi_related_grid">
	<?php

	/**
	 * Allows adjusting the image size.
	 *
	 * The default value for the image size is "post-thumbnail", but if you for
	 * example want a non-square image size, you can use this filter to set the
	 * image size to "medium".
	 *
	 * @param string The image size.
	 */
	$image_size = apply_filters( 'relevanssi_related_image_size', 'post-thumbnail' );

	/**
	 * The related posts are stored in the $related_posts WP_Query object. Each
	 * post is just the post ID number, so if you want to access the whole post
	 * object, use get_post() to fetch it.
	 */
	foreach ( $related_posts as $related_post_id ) {
		$the_post     = get_post( $related_post_id );
		$related_link = get_permalink( $related_post_id );

		$class = '';
		$thumb = '';
		if ( isset( $style['thumbnails'] ) && 'off' !== $style['thumbnails'] ) {
			if ( has_post_thumbnail( $related_post_id ) ) {
				$thumb = get_the_post_thumbnail( $related_post_id, $image_size );
			}
			if ( ! $thumb && isset( $style['default_thumbnail'] ) ) {
				$thumb = wp_get_attachment_image( $style['default_thumbnail'], $image_size, false, array( 'class' => 'wp-post-image' ) );
			}
		}

		$excerpt = '';
		if ( isset( $style['excerpts'] ) && 'off' !== $style['excerpts'] ) {
			if ( empty( $the_post->post_excerpt ) ) {
				$the_post->post_excerpt = mb_substr( wp_strip_all_tags( $the_post->post_content ), 0, apply_filters( 'relevanssi_related_excerpt_length', 50 ) ) . '...';
			}
			$excerpt = '<p>' . $the_post->post_excerpt . '</p>';
			if ( mb_strlen( $excerpt ) > apply_filters( 'relevanssi_related_wide_limit', 100 ) ) {
				$class .= ' wide';
			}
		}

		$related_title = '';
		if ( isset( $style['titles'] ) && 'off' !== $style['titles'] ) {
			$related_title = $the_post->post_title;
		}

		?>
<div class="relevanssi_related_post<?php echo esc_attr( $class ); ?>">
	<a href="<?php echo $related_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h4><?php echo $related_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h4>
	</a>
		<?php echo $excerpt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
		<?php
	}
	?>

	</div>
</div>

	<?php

	/**
	 * Prints out the default CSS styles. If you include the style for the related posts
	 * to the main CSS file, you can remove this part from your template.
	 */
	$width = 200;
	if ( isset( $style['width'] ) && intval( $style['width'] ) > 0 ) {
		$width = $style['width'];
	}

	$custom_css = "
	#relevanssi_related_grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax( {$width}px, 1fr));
		grid-column-gap: 10px;
	}
	.relevanssi_related_post h4 {
		margin-top: 5px;
		margin-bottom: 20px;
	}
	.relevanssi_related_grid_header {
		grid-area: header;
	}
	.relevanssi_related_post.wide {
		grid-column-end: span 2;
	}
	";
	$handle     = 'relevanssi-related-grid-styles';
	wp_register_style( $handle, false, array(), 1, 'all' );
	wp_add_inline_style( $handle, $custom_css );
	wp_enqueue_style( $handle );


endif; // This if clause checks for empty( $related_posts->posts ).
