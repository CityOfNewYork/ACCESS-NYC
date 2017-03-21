
<a href="<?php echo $edit_url; ?>">
    <strong><?php echo $title; ?></strong>
</a>

<div class="row-actions">


    <span class="edit">
        <a href="<?php echo $edit_url; ?>"><?php _e( 'Edit', 'ninja-forms' ); ?></a> |
    </span>


    <span class="trash">
        <a href="<?php echo $delete_url; ?>"><?php _e( 'Delete', 'ninja-forms' ); ?></a> |
    </span>

    <span class="duplicate">
        <a href="<?php echo $duplicate_url; ?>"><?php _e( 'Duplicate', 'ninja-forms' ); ?></a> |
    </span>

    <span class="bleep">
        <a href="<?php echo $preview_url; ?>"><?php _e( 'Preview Form', 'ninja-forms' ); ?></a> |
    </span>

    <span class="subs">
        <a target="_blank" href="<?php echo $submissions_url; ?>"><?php _e( 'View Submissions', 'ninja-forms' ); ?></a>
    </span>

</div>
