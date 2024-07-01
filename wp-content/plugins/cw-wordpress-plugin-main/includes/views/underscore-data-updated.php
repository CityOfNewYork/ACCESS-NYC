<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<# if ( false === data.current || data.current ) { #>
<div class="gc-status-warning <# if ( false === data.current ) { #>not-<# } #>current">Your mapping was changed, please
	reimport.
</div>
<span class="gc-status-status <# if ( false === data.current ) { #>not-<# } #>current"
	  title="<# if ( data.current ) { #>{{ data.ptLabel }} <?php esc_attr_e( 'is current.', 'content-workflow-by-bynder' ); ?><# } else { #>{{ data.ptLabel }} <?php esc_attr_e( 'is behind.', 'content-workflow-by-bynder' ); ?><# } #>">{{{ data.updated_at }}}</span>
<# } else { #>
{{{ data.updated_at }}}
<# } #>
