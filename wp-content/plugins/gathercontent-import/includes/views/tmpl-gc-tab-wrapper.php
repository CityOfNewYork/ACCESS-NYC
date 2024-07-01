<# if ( data.label ) { #><legend class="screen-reader-text">{{ data.label }}</legend><# } #>
<table class="widefat" <# if ( data.table_id ) { #>id="{{ data.table_id }}"<# } #>>
	<thead>
		<tr>
			<th class="{{ data.col_headings.gc.id }}'">{{ data.col_headings.gc.label }}</th>
			<th class="{{ data.col_headings.wp.id }}'">{{ data.col_headings.wp.label }}</th>
			<# if ( data.col_headings.gcafter ) { #><th class="{{ data.col_headings.gcafter.id }}'">{{ data.col_headings.gcafter.label }}</th><# } #>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
