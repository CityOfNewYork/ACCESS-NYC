<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<span class="gc-status-color <# if ( '#ffffff' === data.status.color ) { #> gc-status-color-white<# } #>"
	  style="background-color:{{ data.status.color ?? '#fff' }};" data-id="{{ data.status_id }}"></span>
<span class="gc-status-name">{{ data.status_name ? data.status_name : ( data.status.display_name ?? 'N/A' ) }}</span>
