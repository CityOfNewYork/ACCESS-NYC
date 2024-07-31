<# if ( data.mappingLink ) { #>
<a href="{{ data.mappingLink }}">
	<# if ( data.mappingStatus ) { #>
	{{ data.mappingStatus }}
	<# } else { #>
	{{{ data.mappingName }}}
	<# } #>
</a>
<# } else { #>
{{{ data.mappingName }}}
<# } #>
