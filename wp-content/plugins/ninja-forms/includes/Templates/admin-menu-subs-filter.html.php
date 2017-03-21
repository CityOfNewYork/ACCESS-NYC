<select name="form_id" id="form_id">
    <option value="0"><?php echo __( '- Select a form', 'ninja-forms' ); ?></option>
    <?php foreach( $form_options as $id => $title ): ?>
        <option value="<?php echo $id; ?>" <?php if( $id == $form_selected ) echo 'selected'; ?>>
            <?php echo $title; ?>
        </option>
    <?php endforeach; ?>
</select>

<?php if( isset( $_GET[ 'form_id' ] ) ): ?>
<input type="text" name="begin_date" class="datepicker" placeholder="<?php echo __( 'Begin Date', 'ninja-forms' ); ?>" value="<?php echo $begin_date; ?>">

<input type="text" name="end_date" class="datepicker" placeholder="<?php echo __( 'End Date', 'ninja-forms' ); ?>" value="<?php echo $end_date; ?>">
<?php endif; ?>

<script>
    jQuery( document).ready( function($) {

        $( '.datepicker').datepicker();

        $( '#form_id' ).change(function () {

            $( this ).parents( 'form:first' ).submit();

        });
    });
</script>