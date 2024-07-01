<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<a href="#"
   class="gc-reveal-items dashicons-before dashicons-arrow-right description <?php $this->output( 'class', 'esc_attr' ); ?>"><?php esc_html_e( 'Sample of Items', 'content-workflow-by-bynder' ); ?> </a>
<ul class="<?php $this->output( 'class', 'esc_attr' ); ?> gc-reveal-items-list hidden">
	<?php foreach ( $this->get( 'items' ) as $item ) : ?>
		<li><a href="<?php $this->output( 'item_base_url', 'esc_url' ); ?><?php echo esc_attr( $item->id ); ?>"
			   target="_blank"><?php echo esc_attr( $item->name ); ?></a></li>
	<?php endforeach; ?>
</ul>
