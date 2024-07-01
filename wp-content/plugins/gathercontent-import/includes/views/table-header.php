<th scope="col" class="gc-field-th sortable {{ data.sortDirection }} <# if ( '<?php $this->output( 'sort_key', 'esc_attr' ); ?>' === data.sortKey ) { #>sorted <# } #>">
	<a href="#" data-id="<?php $this->output( 'sort_key', 'esc_attr' ); ?>">
		<span><?php $this->output( 'label', 'esc_html' ); ?></span><span class="sorting-indicator"></span>
	</a>
</th>
