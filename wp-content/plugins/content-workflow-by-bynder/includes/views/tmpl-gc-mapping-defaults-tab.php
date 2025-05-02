<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<tr id="default-mapping-post_type">
	<td>
		<strong><?php $this->output( 'post_type_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select" data-column="post_type"
				name="<?php $this->output( 'option_base' ); ?>[post_type]">
			<# if ( data.initial ) { #>
			<option selected="selected" value=""><?php esc_html_e( 'Select', 'content-workflow-by-bynder' ); ?></option>
			<# } #>
			<?php foreach ( $this->get( 'post_type_options' ) as $option_val => $option_label ) : ?>
				<option <# if ( '<?php echo esc_attr( $option_val ); ?>' === data.post_type && ! data.initial ) { #>selected="selected"<# } #> value="<?php echo esc_attr( $option_val ); ?>"><?php echo esc_html( $option_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
<tr id="default-mapping-post_author">
	<td>
		<strong><?php $this->output( 'post_author_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select gc-select2"
				data-url="<?php echo esc_url( admin_url( 'admin-ajax.php?action=cwby_get_option_data' ) ); ?>"
				data-column="post_author" name="<?php $this->output( 'option_base' ); ?>[post_author]">
			<# if ( data.post_author && data[ 'select2:post_author:' + data.post_author ] ) { #>
			<option selected="selected" value="{{ data.post_author }}">{{ data['select2:post_author:' +
				data.post_author] }}
			</option>
			<# } #>
		</select>
	</td>
</tr>
<tr id="default-mapping-post_status">
	<td>
		<strong><?php $this->output( 'post_status_label' ); ?></strong>
	</td>
	<td>
		<select class="gc-default-mapping-select" data-column="post_status"
				name="<?php $this->output( 'option_base' ); ?>[post_status]">
			<?php foreach ( $this->get( 'post_status_options' ) as $option_val => $option_label ) : ?>
				<option <# if ( '<?php echo esc_attr( $option_val ); ?>' === data.post_status ) { #>selected="selected"<# } #> value="<?php echo esc_attr( $option_val ); ?>"><?php echo esc_html( $option_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
</tr>
