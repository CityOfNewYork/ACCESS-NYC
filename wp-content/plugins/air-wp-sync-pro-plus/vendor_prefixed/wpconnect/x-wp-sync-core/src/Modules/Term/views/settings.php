<?php
/**
 * Displays term module settings.
 *
 * @package wpconnect/x-wp-sync-core
 */

/**
 * Display the term settings
 *
 * @param WP_Term[] $taxonomies Available taxonomies
 */
return function ( $taxonomies ) {
	?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">
				<label for="test"><?php esc_html_e( 'Default taxonomy', 'air-wp-sync-pro-plus' ); ?></label>
			</th>
			<td>
				<select class="regular-text ltr" name="airwpsync::taxonomy" x-model="config.taxonomy" x-init="config.taxonomy = config.taxonomy || $el.value;">
					<?php foreach ( $taxonomies as $slug => $taxonomy ) : ?>
						<option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>
	<?php
};
