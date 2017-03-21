<span class="gc-status-column" data-id="{{ data.id }}" data-item="{{ data.item }}" data-mapping="{{ data.mapping }}">
<# if ( data.status.name ) { #>
	<div class="gc-item-status">
		<?php echo new self( 'underscore-data-status' ); ?>
	</div>
<# } else { #>
	&mdash;
<# } #>
</span>
<?php
	// echo "<# console.log( 'data', data ); #>";
