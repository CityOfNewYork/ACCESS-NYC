<# if ( false === data.current || data.current ) { #>
<span class="gc-status-status <# if ( false === data.current ) { #>not-<# } #>current" title="<# if ( data.current ) { #>{{ data.ptLabel }} <?php esc_attr_e( 'is current.', 'gathercontent-importer' ); ?><# } else { #>{{ data.ptLabel }} <?php esc_attr_e( 'is behind.', 'gathercontent-importer' ); ?><# } #>">{{{ data.updated_at }}}</span>
<# } else { #>
	{{{ data.updated_at }}}
<# } #>
