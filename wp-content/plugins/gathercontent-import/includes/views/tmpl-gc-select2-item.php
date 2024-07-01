<span>
	{{ data.text }}
	<# if ( data.color ) { #>
	<span class="gc-status-color <# if ( '#ffffff' === data.color ) { #> gc-status-color-white<# } #>" style="background-color:{{ data.color }};"></span>
	<# } #>
</span>
<# if ( data.description ) { #>
<div class="description">{{ data.description }}</div>
<# } #>
