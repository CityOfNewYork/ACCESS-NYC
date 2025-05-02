<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php foreach ( $this->get( 'gc_status_options' ) as $status ) : ?>
	<tr id="gc-status-<?php echo esc_attr( $status->id ); ?>">
		<td>
			<div class="gc-item-status">
				<span class="gc-status-color
				<?php
				if ( '#ffffff' === $status->color ) :
					?>
					 gc-status-color-white<?php endif; ?>"
					  style="background-color:<?php echo esc_attr( $status->color ); ?>;"
					  data-id="<?php echo esc_attr( $status->id ); ?>"></span>
				<?php echo esc_attr( $status->display_name ); ?>
			</div>
		</td>
		<td>
			<select class="gc-default-mapping-select" data-column="post_status_mapping"
					name="<?php $this->output( 'option_base' ); ?>[gc_status][<?php echo esc_attr( $status->id ); ?>][wp]">
				<option
				<# if ( ! data.gc_status[<?php echo esc_attr( $status->id ); ?>] || !
				data.gc_status[<?php echo esc_attr( $status->id ); ?>].wp ) { #>selected="selected"<# } #>
				value=""><?php esc_html_e( 'Use Default Status', 'content-workflow-by-bynder' ); ?></option>
				<?php foreach ( $this->get( 'post_status_options' ) as $option_val => $option_label ) : ?>
					<option <# if ( data.gc_status[<?php echo esc_attr( $status->id ); ?>] &&  data.gc_status[<?php echo esc_attr( $status->id ); ?>].wp && '<?php echo esc_attr($option_val); ?>' == data.gc_status[<?php echo esc_attr( $status->id ); ?>].wp ) { #>selected="selected"<# } #> value="<?php echo esc_attr($option_val); ?>"><?php echo esc_html($option_label); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<select class="gc-default-mapping-select gc-select2" data-column="gc_status"
					name="<?php $this->output( 'option_base' ); ?>[gc_status][<?php echo esc_attr( $status->id ); ?>][after]"">
			<option
			<# if ( ! data.gc_status[<?php echo esc_attr( $status->id ); ?>] || !
			data.gc_status[<?php echo esc_attr( $status->id ); ?>].after ) { #>selected="selected"<# } #>
			value=""><?php esc_html_e( 'Do not change', 'content-workflow-by-bynder' ); ?></option>
			<?php foreach ( $this->get( 'gc_status_options' ) as $status2 ) : ?>
				<option data-color="<?php echo esc_attr( $status2->color ); ?>"
						data-description="<?php echo esc_attr( $status2->description ); ?>" <# if ( data.gc_status[<?php echo esc_attr( $status->id ); ?>] && data.gc_status[<?php echo esc_attr( $status->id ); ?>].after && '<?php echo esc_attr( $status2->id ); ?>' == data.gc_status[<?php echo esc_attr( $status->id ); ?>].after ) { #>selected="selected"<# } #> value="<?php echo esc_attr( $status2->id ); ?>"><?php echo esc_attr( $status2->display_name ); ?></option>
			<?php endforeach; ?>
			</select>
		</td>
	</tr>
<?php endforeach; ?>
